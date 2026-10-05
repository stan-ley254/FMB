<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Sale;
use LogicException;

class CompletedOrderSaleService
{
    public function createSale(Order $order): Sale
    {
        if ($order->status !== 'completed') {
            throw new LogicException("Order #{$order->id} must be completed before a sale can be recorded.");
        }

        $order->loadMissing(['customer', 'items.material', 'items.catalogItem']);

        return Sale::firstOrCreate(
            ['order_id' => $order->id],
            [
                'customer_id' => $order->customer_id,
                'total_amount' => $order->total_amount,
                'amount_paid' => $order->amount_paid,
                'payment_method' => $order->payment_method,
                'bank_name' => $order->bank_name,
                'completed_at' => now(),
                'items_snapshot' => $order->items->map(fn (OrderItem $item): array => [
                    'item_type' => $item->item_type,
                    'material_id' => $item->material_id,
                    'material_name' => $item->material?->name,
                    'catalog_item_id' => $item->catalog_item_id,
                    'catalog_item_name' => $item->catalog_item_name,
                    'category' => $item->catalogItem?->category,
                    'unit' => $item->catalog_item_unit,
                    'quantity' => $item->quantity_or_meters,
                    'unit_price' => $item->unit_price,
                    'discount' => $item->discount,
                    'subtotal' => $item->subtotal,
                ])->values()->all(),
            ],
        );
    }
}
