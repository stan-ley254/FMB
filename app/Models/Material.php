<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'category', 'unit', 'quantity_remaining', 'unit_cost', 'machine'])]
class Material extends Model
{
    protected function casts(): array
    {
        return [
            'quantity_remaining' => 'decimal:0',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'related_material_id');
    }
}
