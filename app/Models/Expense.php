<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'category', 'supplier_name', 'item_description', 'quantity_or_size', 'amount',
    'payment_method', 'bank_name', 'adds_to_stock', 'related_material_id',
    'related_ink_stock_id',
])]
class Expense extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'adds_to_stock' => 'boolean',
        ];
    }

    public function relatedMaterial(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'related_material_id');
    }

    public function relatedInkStock(): BelongsTo
    {
        return $this->belongsTo(InkStock::class, 'related_ink_stock_id');
    }
}
