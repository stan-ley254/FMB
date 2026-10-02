<?php

namespace Database\Seeders;

use App\Models\CatalogItem;
use App\Models\Material;
use Illuminate\Database\Seeder;

class CatalogItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Banner 1M', 'category' => 'banner', 'order_item_type' => 'banner', 'unit' => 'meter', 'default_unit_price' => 400, 'material' => 'Banner 1M'],
            ['name' => 'Banner 1.2M', 'category' => 'banner', 'order_item_type' => 'banner', 'unit' => 'meter', 'default_unit_price' => 400, 'material' => 'Banner 1.2M'],
            ['name' => 'Banner 1.4M', 'category' => 'banner', 'order_item_type' => 'banner', 'unit' => 'meter', 'default_unit_price' => 400, 'material' => 'Banner 1.4M'],
            ['name' => 'Banner 2M', 'category' => 'banner', 'order_item_type' => 'banner', 'unit' => 'meter', 'default_unit_price' => 400, 'material' => 'Banner 2M'],
            ['name' => 'Sertine 1M', 'category' => 'sertine', 'order_item_type' => 'sertine', 'unit' => 'meter', 'default_unit_price' => 1000, 'material' => 'Sertine 1M'],
            ['name' => 'DTF NoCut Printing', 'category' => null, 'order_item_type' => 'dtf_print', 'unit' => 'meter', 'default_unit_price' => 450, 'material' => null],
            ['name' => 'White Sticker Printing', 'category' => 'sticker', 'order_item_type' => 'sticker', 'unit' => 'meter', 'default_unit_price' => 400, 'material' => 'Sticker 1M'],
            ['name' => 'Sticker A3', 'category' => 'sticker', 'order_item_type' => 'sticker', 'unit' => 'piece', 'default_unit_price' => 50, 'material' => null],
            ['name' => 'Sticker A4', 'category' => 'sticker', 'order_item_type' => 'sticker', 'unit' => 'piece', 'default_unit_price' => 30, 'material' => null],
            ['name' => 'Sticker A5', 'category' => 'sticker', 'order_item_type' => 'sticker', 'unit' => 'piece', 'default_unit_price' => 20, 'material' => null],
            ['name' => 'T-shirt Branding (Shop-sourced)', 'category' => 'garment', 'order_item_type' => 'dtf_garment', 'unit' => 'piece', 'default_unit_price' => 800, 'material' => 'Blank T-Shirt'],
            ['name' => 'T-shirt Branding (Customer-supplied)', 'category' => 'garment', 'order_item_type' => 'dtf_garment', 'unit' => 'piece', 'default_unit_price' => 800, 'material' => null],
        ];

        foreach ($items as $item) {
            $material = $item['material'] === null
                ? null
                : Material::where('name', $item['material'])->value('id');

            CatalogItem::firstOrCreate(
                ['name' => $item['name']],
                [
                    'category' => $item['category'],
                    'order_item_type' => $item['order_item_type'],
                    'unit' => $item['unit'],
                    'default_unit_price' => $item['default_unit_price'],
                    'material_id' => $material,
                    'is_active' => true,
                ],
            );
        }
    }
}
