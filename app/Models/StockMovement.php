<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['material_id', 'ink_stock_id', 'type', 'quantity', 'related_order_id', 'notes'])]
class StockMovement extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'created_at' => 'datetime',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function inkStock(): BelongsTo
    {
        return $this->belongsTo(InkStock::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'related_order_id');
    }
}
