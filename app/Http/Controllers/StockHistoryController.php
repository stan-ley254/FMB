<?php

namespace App\Http\Controllers;

use App\Models\InkStock;
use App\Models\Material;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'item' => ['nullable', 'string', 'regex:/^(material|ink):[1-9][0-9]*$/'],
            'type' => ['nullable', 'in:purchase,usage'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $movements = StockMovement::query()
            ->with(['material', 'inkStock'])
            ->when($validated['item'] ?? null, function (Builder $query, string $item): void {
                [$itemType, $itemId] = explode(':', $item);

                $query->where($itemType === 'material' ? 'material_id' : 'ink_stock_id', $itemId);
            })
            ->when($validated['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($validated['from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($validated['to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->latest('created_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $usageMovements = collect();

        if ($movements->isNotEmpty()) {
            $usageMovements = StockMovement::query()
                ->where('type', 'usage')
                ->where(function (Builder $query) use ($movements): void {
                    foreach ($movements->getCollection()->groupBy(fn (StockMovement $movement): string => $this->itemKey($movement)) as $itemMovements) {
                        $movement = $itemMovements->first();

                        $query->orWhere(function (Builder $itemQuery) use ($movement): void {
                            $itemQuery
                                ->when(
                                    $movement->material_id !== null,
                                    fn (Builder $query) => $query->where('material_id', $movement->material_id)->whereNull('ink_stock_id'),
                                    fn (Builder $query) => $query->whereNull('material_id')->where('ink_stock_id', $movement->ink_stock_id),
                                );
                        });
                    }
                })
                ->oldest('created_at')
                ->oldest('id')
                ->get();
        }

        $previousUsageAt = [];
        $lastUsageAt = [];

        foreach ($usageMovements as $usageMovement) {
            $itemKey = $this->itemKey($usageMovement);
            $previousUsageAt[$usageMovement->id] = $lastUsageAt[$itemKey] ?? null;
            $lastUsageAt[$itemKey] = $usageMovement->created_at;
        }

        $movements->getCollection()->transform(function (StockMovement $movement) use ($previousUsageAt): StockMovement {
            $movement->setAttribute('previous_usage_at', $movement->type === 'usage'
                ? ($previousUsageAt[$movement->id] ?? null)
                : null);

            return $movement;
        });

        return view('stock.history', [
            'movements' => $movements,
            'materials' => Material::orderBy('name')->get(),
            'inkStocks' => InkStock::orderBy('machine')->orderBy('color')->get(),
            'filters' => $validated,
        ]);
    }

    private function itemKey(StockMovement $movement): string
    {
        return $movement->material_id !== null
            ? "material:{$movement->material_id}"
            : "ink:{$movement->ink_stock_id}";
    }
}
