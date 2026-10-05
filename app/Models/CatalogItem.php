<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'category', 'order_item_type', 'unit', 'default_unit_price',
    'material_id', 'is_active',
])]
class CatalogItem extends Model
{
    public const CATEGORIES = [
        'banner' => 'Banner',
        'sertine' => 'Sertine',
        'sticker' => 'Sticker',
        'dtf-consumable' => 'DTF consumables',
        'garment' => 'Garment',
        'Receipt Books' => 'Receipt Books',
        'Paper Printing' => 'Paper Printing',
        'Caps' => 'Caps',
        'Design' => 'Design',
        'Stamps & Seals' => 'Stamps & Seals',
        'Reflectors' => 'Reflectors',
        'Mugs' => 'Mugs',
        'Bottles' => 'Bottles',
        'T-Shirts' => 'T-Shirts',
        'Pens' => 'Pens',
        'Other' => 'Other',
        'Sub Printing' => 'Sub Printing',
    ];

    public const ITEM_TYPES = [
        'banner' => 'Banner',
        'sertine' => 'Sertine',
        'sticker' => 'Sticker',
        'dtf_garment' => 'DTF Garment',
        'dtf_print' => 'DTF Print',
        'receipt_books' => 'Receipt Books',
        'paper_printing' => 'Paper Printing',
        'caps' => 'Caps',
        'design' => 'Design',
        'stamps_seals' => 'Stamps & Seals',
        'reflectors' => 'Reflectors',
        'mugs' => 'Mugs',
        'bottles' => 'Bottles',
        't_shirts' => 'T-Shirts',
        'pens' => 'Pens',
        'other' => 'Other',
        'sub_printing' => 'Sub Printing',
    ];

    public const EXPECTED_CATEGORIES = [
        'banner' => 'banner',
        'sertine' => 'sertine',
        'sticker' => 'sticker',
        'dtf_garment' => 'garment',
        'dtf_print' => null,
        'receipt_books' => 'Receipt Books',
        'paper_printing' => 'Paper Printing',
        'caps' => 'Caps',
        'design' => 'Design',
        'stamps_seals' => 'Stamps & Seals',
        'reflectors' => 'Reflectors',
        'mugs' => 'Mugs',
        'bottles' => 'Bottles',
        't_shirts' => 'T-Shirts',
        'pens' => 'Pens',
        'other' => 'Other',
        'sub_printing' => 'Sub Printing',
    ];

    protected function casts(): array
    {
        return [
            'default_unit_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
