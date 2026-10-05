<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CATEGORY_TYPES = [
        'Receipt Books' => 'receipt_books',
        'Paper Printing' => 'paper_printing',
        'Caps' => 'caps',
        'Design' => 'design',
        'Stamps & Seals' => 'stamps_seals',
        'Reflectors' => 'reflectors',
        'Mugs' => 'mugs',
        'Bottles' => 'bottles',
        'T-Shirts' => 't_shirts',
        'Pens' => 'pens',
        'Other' => 'other',
        'Sub Printing' => 'sub_printing',
    ];

    public function up(): void
    {
        $catalogItems = DB::table('catalog_items')
            ->whereIn('category', array_keys(self::CATEGORY_TYPES))
            ->get(['id', 'category']);
        $catalogItemTypes = [];

        foreach ($catalogItems as $catalogItem) {
            $itemType = self::CATEGORY_TYPES[$catalogItem->category];
            $catalogItemTypes[$catalogItem->id] = [
                'item_type' => $itemType,
                'category' => $catalogItem->category,
            ];

            DB::table('catalog_items')
                ->where('id', $catalogItem->id)
                ->update(['order_item_type' => $itemType]);

            DB::table('order_items')
                ->where('catalog_item_id', $catalogItem->id)
                ->update(['item_type' => $itemType]);
        }

        DB::table('sales')
            ->whereNotNull('items_snapshot')
            ->select(['id', 'items_snapshot'])
            ->orderBy('id')
            ->chunk(200, function ($sales) use ($catalogItemTypes): void {
                foreach ($sales as $sale) {
                    $snapshot = json_decode($sale->items_snapshot, true, flags: JSON_THROW_ON_ERROR);
                    $changed = false;

                    foreach ($snapshot as &$item) {
                        $catalogItemId = $item['catalog_item_id'] ?? null;

                        if ($catalogItemId === null || ! isset($catalogItemTypes[$catalogItemId])) {
                            continue;
                        }

                        $item['item_type'] = $catalogItemTypes[$catalogItemId]['item_type'];
                        $item['category'] = $catalogItemTypes[$catalogItemId]['category'];
                        $changed = true;
                    }
                    unset($item);

                    if ($changed) {
                        DB::table('sales')
                            ->where('id', $sale->id)
                            ->update(['items_snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR)]);
                    }
                }
            });
    }

    public function down(): void
    {
        $catalogItemIds = DB::table('catalog_items')
            ->whereIn('category', array_keys(self::CATEGORY_TYPES))
            ->pluck('id')
            ->all();

        if ($catalogItemIds === []) {
            return;
        }

        DB::table('catalog_items')
            ->whereIn('id', $catalogItemIds)
            ->update(['order_item_type' => 'dtf_print']);
        DB::table('order_items')
            ->whereIn('catalog_item_id', $catalogItemIds)
            ->update(['item_type' => 'dtf_print']);

        DB::table('sales')
            ->whereNotNull('items_snapshot')
            ->select(['id', 'items_snapshot'])
            ->orderBy('id')
            ->chunk(200, function ($sales) use ($catalogItemIds): void {
                foreach ($sales as $sale) {
                    $snapshot = json_decode($sale->items_snapshot, true, flags: JSON_THROW_ON_ERROR);
                    $changed = false;

                    foreach ($snapshot as &$item) {
                        if (! in_array($item['catalog_item_id'] ?? null, $catalogItemIds)) {
                            continue;
                        }

                        $item['item_type'] = 'dtf_print';
                        unset($item['category']);
                        $changed = true;
                    }
                    unset($item);

                    if ($changed) {
                        DB::table('sales')
                            ->where('id', $sale->id)
                            ->update(['items_snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR)]);
                    }
                }
            });
    }
};
