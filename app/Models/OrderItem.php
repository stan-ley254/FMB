<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'order_id', 'item_type', 'material_id', 'catalog_item_id', 'catalog_item_name', 'catalog_item_unit',
    'quantity_or_meters', 'unit_price', 'discount', 'garment_sourced_by_shop', 'subtotal',
])]
class OrderItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity_or_meters' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'discount' => 'decimal:2',
            'garment_sourced_by_shop' => 'boolean',
            'subtotal' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    public function artworks(): HasMany
    {
        return $this->hasMany(OrderArtwork::class);
    }
}
