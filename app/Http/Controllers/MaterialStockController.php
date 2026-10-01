<?php

namespace App\Http\Controllers;

use App\Models\InkStock;
use App\Models\Material;
use App\Services\StockPurchaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MaterialStockController extends Controller
{
    public function index(): View
    {
        return view('stock.index', [
            'materialsByCategory' => Material::query()
                ->withMax([
                    'stockMovements as last_used_at' => fn (Builder $query) => $query->where('type', 'usage'),
                    'stockMovements as last_restocked_at' => fn (Builder $query) => $query->where('type', 'purchase'),
                ], 'created_at')
                ->orderBy('name')
                ->get()
                ->groupBy('category'),
            'inkStocksByMachine' => InkStock::query()
                ->withMax([
                    'stockMovements as last_used_at' => fn (Builder $query) => $query->where('type', 'usage'),
                    'stockMovements as last_restocked_at' => fn (Builder $query) => $query->where('type', 'purchase'),
                ], 'created_at')
                ->orderBy('machine')
                ->orderBy('color')
                ->get()
                ->groupBy('machine'),
            'categories' => [
                'banner' => 'Banner widths',
                'sertine' => 'Sertine',
                'sticker' => 'Stickers',
                'dtf-consumable' => 'DTF consumables',
                'garment' => 'Blank garments',
            ],
        ]);
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
}
