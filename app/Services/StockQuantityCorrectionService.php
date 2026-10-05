<?php

namespace App\Services;

use App\Models\InkStock;
use App\Models\Material;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockQuantityCorrectionService
{
    public function correctMaterialQuantity(Material $material, int $quantity): ?StockMovement
    {
        return DB::transaction(function () use ($material, $quantity): ?StockMovement {
            $lockedMaterial = Material::whereKey($material->id)->lockForUpdate()->firstOrFail();
            $difference = $quantity - (int) $lockedMaterial->quantity_remaining;

            if ($difference === 0) {
                return null;
            }

            $lockedMaterial->update(['quantity_remaining' => $quantity]);

            return $lockedMaterial->stockMovements()->create([
                'type' => 'purchase',
                'quantity' => $difference,
                'notes' => 'Manual correction via edit form.',
            ]);
        });
    }

    public function correctInkQuantity(InkStock $inkStock, int $quantity): ?StockMovement
    {
        return DB::transaction(function () use ($inkStock, $quantity): ?StockMovement {
            $lockedInkStock = InkStock::whereKey($inkStock->id)->lockForUpdate()->firstOrFail();
            $difference = $quantity - (int) $lockedInkStock->quantity_remaining;

            if ($difference === 0) {
                return null;
            }

            $lockedInkStock->update(['quantity_remaining' => $quantity]);

            return $lockedInkStock->stockMovements()->create([
                'type' => 'purchase',
                'quantity' => $difference,
                'notes' => 'Manual correction via edit form.',
            ]);
        });
    }
}
