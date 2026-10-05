<?php

namespace App\Http\Controllers;

use App\Models\InkStock;
use App\Models\Material;
use App\Services\StockPurchaseService;
use App\Services\StockQuantityCorrectionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MaterialStockController extends Controller
{
    private const MATERIAL_CATEGORIES = [
        'banner' => 'Banner widths',
        'sertine' => 'Sertine',
        'sticker' => 'Stickers',
        'dtf-consumable' => 'DTF consumables',
        'garment' => 'Blank garments',
    ];

    private const MATERIAL_UNITS = ['rolls', 'sachets', 'pieces'];

    private const MACHINES = [
        'large_format' => 'Large Format (I3200)',
        'dtf' => 'DTF Printer',
    ];

    private const INK_COLORS = ['cyan', 'magenta', 'yellow', 'black', 'white'];

    public function index(): View
    {
        return view('stock.index', [
            'materialsByCategory' => Material::query()
                ->withExists(['stockMovements', 'orderItems', 'expenses'])
                ->withMax([
                    'stockMovements as last_used_at' => fn (Builder $query) => $query->where('type', 'usage'),
                    'stockMovements as last_restocked_at' => fn (Builder $query) => $query->where('type', 'purchase'),
                ], 'created_at')
                ->orderBy('name')
                ->get()
                ->groupBy('category'),
            'inkStocksByMachine' => InkStock::query()
                ->withExists(['stockMovements', 'expenses'])
                ->withMax([
                    'stockMovements as last_used_at' => fn (Builder $query) => $query->where('type', 'usage'),
                    'stockMovements as last_restocked_at' => fn (Builder $query) => $query->where('type', 'purchase'),
                ], 'created_at')
                ->orderBy('machine')
                ->orderBy('color')
                ->get()
                ->groupBy('machine'),
            'categories' => self::MATERIAL_CATEGORIES,
            'hasAvailableInkCombination' => $this->availableInkCombinations() !== [],
        ]);
    }

    public function createMaterial(): View
    {
        return view('stock.materials.create', [
            'categories' => self::MATERIAL_CATEGORIES,
            'units' => self::MATERIAL_UNITS,
            'machines' => self::MACHINES,
        ]);
    }

    public function storeMaterial(Request $request, StockPurchaseService $stockPurchaseService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('materials', 'name')],
            'category' => ['required', Rule::in(array_keys(self::MATERIAL_CATEGORIES))],
            'unit' => ['required', Rule::in(self::MATERIAL_UNITS)],
            'machine' => ['nullable', Rule::in(array_keys(self::MACHINES))],
            'starting_quantity' => ['required', 'numeric', 'integer', 'min:0', 'max:999999'],
        ]);

        $material = DB::transaction(function () use ($validated, $stockPurchaseService): Material {
            $material = Material::create([
                'name' => $validated['name'],
                'category' => $validated['category'],
                'unit' => $validated['unit'],
                'machine' => $validated['machine'] ?? null,
                'quantity_remaining' => 0,
                'is_active' => true,
            ]);

            if ((int) $validated['starting_quantity'] > 0) {
                $stockPurchaseService->addMaterial($material, (int) $validated['starting_quantity'], 'Starting stock.');
            }

            return $material;
        });

        return redirect()->route('stock.index')->with('success', "{$material->name} added to materials.");
    }

    public function editMaterial(Material $material): View
    {
        return view('stock.materials.edit', [
            'material' => $material,
            'categories' => self::MATERIAL_CATEGORIES,
            'units' => self::MATERIAL_UNITS,
            'machines' => self::MACHINES,
            'canDelete' => ! $this->materialHasReferences($material),
        ]);
    }

    public function updateMaterial(
        Request $request,
        Material $material,
        StockQuantityCorrectionService $stockQuantityCorrectionService,
    ): RedirectResponse {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('materials', 'name')->ignore($material->id)],
            'category' => ['required', Rule::in(array_keys(self::MATERIAL_CATEGORIES))],
            'unit' => ['required', Rule::in(self::MATERIAL_UNITS)],
            'machine' => ['nullable', Rule::in(array_keys(self::MACHINES))],
            'is_active' => ['required', 'boolean'],
            'quantity_remaining' => ['sometimes', 'required', 'integer', 'min:0', 'max:999999'],
            'starting_quantity' => ['prohibited'],
        ]);

        DB::transaction(function () use ($material, $validated, $stockQuantityCorrectionService): void {
            $lockedMaterial = Material::whereKey($material->id)->lockForUpdate()->firstOrFail();
            $lockedMaterial->update([
                'name' => $validated['name'],
                'category' => $validated['category'],
                'unit' => $validated['unit'],
                'machine' => $validated['machine'] ?? null,
                'is_active' => $validated['is_active'],
            ]);

            if (array_key_exists('quantity_remaining', $validated)) {
                $stockQuantityCorrectionService->correctMaterialQuantity(
                    $lockedMaterial,
                    (int) $validated['quantity_remaining'],
                );
            }
        });

        return redirect()->route('stock.index')->with('success', "{$material->name} updated.");
    }

    public function destroyMaterial(Material $material): RedirectResponse
    {
        $wasDeactivated = DB::transaction(function () use ($material): bool {
            $lockedMaterial = Material::whereKey($material->id)->lockForUpdate()->firstOrFail();

            if ($this->materialHasReferences($lockedMaterial)) {
                $lockedMaterial->update(['is_active' => false]);

                return true;
            }

            $lockedMaterial->delete();

            return false;
        });

        return redirect()->route('stock.index')->with(
            'success',
            $wasDeactivated ? "{$material->name} deactivated because it has history." : "{$material->name} deleted.",
        );
    }

    public function createInk(): View
    {
        return view('stock.inks.create', [
            'combinations' => $this->availableInkCombinations(),
        ]);
    }

    public function storeInk(Request $request, StockPurchaseService $stockPurchaseService): RedirectResponse
    {
        $combinations = $this->availableInkCombinations();
        $validated = $request->validate([
            'combination' => ['required', Rule::in(array_keys($combinations))],
            'starting_quantity' => ['required', 'numeric', 'integer', 'min:0', 'max:999999'],
        ]);
        [$machine, $color] = explode('|', $validated['combination']);

        $inkStock = DB::transaction(function () use ($machine, $color, $validated, $stockPurchaseService): InkStock {
            $inkStock = InkStock::create([
                'machine' => $machine,
                'color' => $color,
                'unit' => 'bottles',
                'quantity_remaining' => 0,
                'is_active' => true,
            ]);

            if ((int) $validated['starting_quantity'] > 0) {
                $stockPurchaseService->addInk($inkStock, (int) $validated['starting_quantity'], 'Starting stock.');
            }

            return $inkStock;
        });

        return redirect()->route('stock.index')->with('success', 'Ink stock added.');
    }

    public function editInk(InkStock $inkStock): View
    {
        return view('stock.inks.edit', [
            'inkStock' => $inkStock,
            'canDelete' => ! $this->inkHasReferences($inkStock),
        ]);
    }

    public function updateInk(
        Request $request,
        InkStock $inkStock,
        StockQuantityCorrectionService $stockQuantityCorrectionService,
    ): RedirectResponse {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
            'machine' => ['prohibited'],
            'color' => ['prohibited'],
            'unit' => ['prohibited'],
            'quantity_remaining' => ['sometimes', 'required', 'integer', 'min:0', 'max:999999'],
            'starting_quantity' => ['prohibited'],
        ]);

        DB::transaction(function () use ($inkStock, $validated, $stockQuantityCorrectionService): void {
            $lockedInkStock = InkStock::whereKey($inkStock->id)->lockForUpdate()->firstOrFail();
            $lockedInkStock->update(['is_active' => $validated['is_active']]);

            if (array_key_exists('quantity_remaining', $validated)) {
                $stockQuantityCorrectionService->correctInkQuantity(
                    $lockedInkStock,
                    (int) $validated['quantity_remaining'],
                );
            }
        });

        return redirect()->route('stock.index')->with('success', 'Ink stock updated.');
    }

    public function destroyInk(InkStock $inkStock): RedirectResponse
    {
        $wasDeactivated = DB::transaction(function () use ($inkStock): bool {
            $lockedInkStock = InkStock::whereKey($inkStock->id)->lockForUpdate()->firstOrFail();

            if ($this->inkHasReferences($lockedInkStock)) {
                $lockedInkStock->update(['is_active' => false]);

                return true;
            }

            $lockedInkStock->delete();

            return false;
        });

        return redirect()->route('stock.index')->with(
            'success',
            $wasDeactivated ? 'Ink stock deactivated because it has history.' : 'Ink stock deleted.',
        );
    }

    public function addMaterial(Request $request, Material $material, StockPurchaseService $stockPurchaseService): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'integer', 'gt:0', 'max:999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $stockPurchaseService->addMaterial($material, (int) $validated['quantity'], $validated['notes'] ?? null);

        return back()->with('success', "Stock added to {$material->name}.");
    }

    public function useMaterial(Material $material): RedirectResponse
    {
        DB::transaction(function () use ($material): void {
            $lockedMaterial = Material::whereKey($material->id)->lockForUpdate()->firstOrFail();

            if ((float) $lockedMaterial->quantity_remaining < 1) {
                abort(422, "There is no {$lockedMaterial->name} left in stock.");
            }

            $lockedMaterial->decrement('quantity_remaining', 1);
            $lockedMaterial->stockMovements()->create([
                'type' => 'usage',
                'quantity' => 1,
                'notes' => 'Manually marked as used.',
            ]);
        });

        return back()->with('success', "One unit of {$material->name} marked as used.");
    }

    public function addInk(Request $request, InkStock $inkStock, StockPurchaseService $stockPurchaseService): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'integer', 'gt:0', 'max:999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $stockPurchaseService->addInk($inkStock, (int) $validated['quantity'], $validated['notes'] ?? null);

        return back()->with('success', 'Ink stock added.');
    }

    public function useInk(InkStock $inkStock): RedirectResponse
    {
        DB::transaction(function () use ($inkStock): void {
            $lockedInkStock = InkStock::whereKey($inkStock->id)->lockForUpdate()->firstOrFail();

            if ((float) $lockedInkStock->quantity_remaining < 1) {
                abort(422, 'There is no ink bottle left in stock.');
            }

            $lockedInkStock->decrement('quantity_remaining', 1);
            $lockedInkStock->stockMovements()->create([
                'type' => 'usage',
                'quantity' => 1,
                'notes' => 'Manually marked as used.',
            ]);
        });

        return back()->with('success', 'One ink bottle marked as used.');
    }

    private function materialHasReferences(Material $material): bool
    {
        return $material->stockMovements()->exists()
            || $material->orderItems()->exists()
            || $material->expenses()->exists();
    }

    private function inkHasReferences(InkStock $inkStock): bool
    {
        return $inkStock->stockMovements()->exists()
            || $inkStock->expenses()->exists();
    }

    /**
     * @return array<string, string>
     */
    private function availableInkCombinations(): array
    {
        $configured = InkStock::query()
            ->get(['machine', 'color'])
            ->mapWithKeys(fn (InkStock $inkStock): array => [$inkStock->machine.'|'.$inkStock->color => true]);
        $available = [];

        foreach (self::MACHINES as $machine => $machineLabel) {
            foreach (self::INK_COLORS as $color) {
                $key = $machine.'|'.$color;

                if (! $configured->has($key)) {
                    $available[$key] = $machineLabel.' — '.str($color)->title();
                }
            }
        }

        return $available;
    }
}
