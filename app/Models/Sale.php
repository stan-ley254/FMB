<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id', 'customer_id', 'total_amount', 'amount_paid', 'payment_method',
    'bank_name', 'completed_at', 'items_snapshot',
])]
class Sale extends Model
{
    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'completed_at' => 'datetime',
            'items_snapshot' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function itemsSummary(): string
    {
        return collect($this->items_snapshot)
            ->map(function (array $item): string {
                $itemType = $item['item_type'] ?? '';
                $typeLabel = match ($itemType) {
                    'dtf_garment' => 'DTF Garment',
                    'dtf_print' => 'DTF Print',
                    'banner' => 'Banner',
                    'sertine' => 'Sertine',
                    'sticker' => 'Sticker',
                    default => str($itemType)->replace('_', ' ')->title()->toString(),
                };
                $materialName = trim((string) ($item['material_name'] ?? ''));
                $catalogItemName = trim((string) ($item['catalog_item_name'] ?? ''));
                $itemLabel = $catalogItemName !== ''
                    ? $catalogItemName
                    : ($itemType === 'dtf_garment'
                        ? $typeLabel.($materialName !== '' ? " ({$materialName})" : '')
                        : ($materialName !== '' ? $materialName : $typeLabel));
                $quantity = rtrim(rtrim(number_format((float) ($item['quantity'] ?? 0), 3, '.', ''), '0'), '.');
                $unit = ($item['unit'] ?? null) === 'piece' || $itemType === 'dtf_garment' ? 'pc' : 'm';

                return "{$quantity} {$unit} {$itemLabel}";
            })
            ->implode(', ');
    }
}
