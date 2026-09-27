<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['machine', 'color', 'quantity_remaining'])]
class InkStock extends Model
{
    protected function casts(): array
    {
        return [
            'quantity_remaining' => 'decimal:3',
        ];
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'related_ink_stock_id');
    }
}
