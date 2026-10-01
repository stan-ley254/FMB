<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\InkStock;
use App\Models\Material;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use LogicException;

class StockPurchaseService
{
    public function addMaterial(Material $material, int $quantity, ?string $notes = null): StockMovement
    {
        return DB::transaction(function () use ($material, $quantity, $notes): StockMovement {
            $lockedMaterial = Material::whereKey($material->id)->lockForUpdate()->firstOrFail();
            $lockedMaterial->increment('quantity_remaining', $quantity);

            return $lockedMaterial->stockMovements()->create([
                'type' => 'purchase',
                'quantity' => $quantity,
                'notes' => $notes,
            ]);
        });
    }

    public function addInk(InkStock $inkStock, int $quantity, ?string $notes = null): StockMovement
    {
        return DB::transaction(function () use ($inkStock, $quantity, $notes): StockMovement {
            $lockedInkStock = InkStock::whereKey($inkStock->id)->lockForUpdate()->firstOrFail();
            $lockedInkStock->increment('quantity_remaining', $quantity);

            return $lockedInkStock->stockMovements()->create([
                'type' => 'purchase',
                'quantity' => $quantity,
                'notes' => $notes,
            ]);
        });
    }

    public function reverseExpensePurchase(Expense $expense): void
    {
        $quantity = (int) $expense->quantity_or_size;
        $notes = "Reversal of expense #{$expense->id} purchase.";

        DB::transaction(function () use ($expense, $quantity, $notes): void {
            if ($expense->related_material_id !== null) {
                $material = Material::whereKey($expense->related_material_id)->lockForUpdate()->firstOrFail();
                $material->decrement('quantity_remaining', $quantity);
                $material->stockMovements()->create([
                    'type' => 'purchase',
                    'quantity' => -$quantity,
                    'notes' => $notes,
                ]);

                return;
            }

            if ($expense->related_ink_stock_id !== null) {
                $inkStock = InkStock::whereKey($expense->related_ink_stock_id)->lockForUpdate()->firstOrFail();
                $inkStock->decrement('quantity_remaining', $quantity);
                $inkStock->stockMovements()->create([
                    'type' => 'purchase',
                    'quantity' => -$quantity,
                    'notes' => $notes,
                ]);

                return;
            }

            throw new LogicException("Expense #{$expense->id} has no linked stock item to reverse.");
        });
    }
}
