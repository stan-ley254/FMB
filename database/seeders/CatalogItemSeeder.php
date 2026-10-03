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
            ['name' => 'A4 Duplicate 1 Color', 'category' => 'Receipt Books', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 600, 'material' => null],
            ['name' => 'A5 100x2', 'category' => 'Receipt Books', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 600, 'material' => null],
            ['name' => 'A5 50x3', 'category' => 'Receipt Books', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 600, 'material' => null],
            ['name' => 'A5 Duplicate 1 Color', 'category' => 'Receipt Books', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 400, 'material' => null],
            ['name' => 'A6 50x2', 'category' => 'Receipt Books', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 300, 'material' => null],
            ['name' => 'A6 100x2', 'category' => 'Receipt Books', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 400, 'material' => null],
            ['name' => 'Artcard A3', 'category' => 'Paper Printing', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 50, 'material' => null],
            ['name' => 'Artpaper A3', 'category' => 'Paper Printing', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 50, 'material' => null],
            ['name' => 'Artpaper A4', 'category' => 'Paper Printing', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 25, 'material' => null],
            ['name' => 'Artpaper A6', 'category' => 'Paper Printing', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 7, 'material' => null],
            ['name' => 'Business Card (Not Laminated)', 'category' => 'Paper Printing', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 10, 'material' => null],
            ['name' => 'Business Card (Laminated)', 'category' => 'Paper Printing', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 20, 'material' => null],
            ['name' => 'Flyers A5', 'category' => 'Paper Printing', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 12, 'material' => null],
            ['name' => 'Posters A3', 'category' => 'Paper Printing', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 50, 'material' => null],
            ['name' => 'Posters A4', 'category' => 'Paper Printing', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 30, 'material' => null],
            ['name' => 'Sticker Print A3', 'category' => 'Paper Printing', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 50, 'material' => null],
            ['name' => 'Sticker Print A4', 'category' => 'Paper Printing', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 30, 'material' => null],
            ['name' => 'Sticker Print A5', 'category' => 'Paper Printing', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 20, 'material' => null],
            ['name' => 'Cap Polyester', 'category' => 'Caps', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 200, 'material' => null],
            ['name' => 'Cap Printed', 'category' => 'Caps', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 350, 'material' => null],
            ['name' => 'Design Only', 'category' => 'Design', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 400, 'material' => null],
            ['name' => 'Digital Seal', 'category' => 'Stamps & Seals', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 5000, 'material' => null],
            ['name' => 'Manual Seal', 'category' => 'Stamps & Seals', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 3500, 'material' => null],
            ['name' => 'Stamp (Self Inking, Auto Date)', 'category' => 'Stamps & Seals', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 2000, 'material' => null],
            ['name' => 'Stamp (Self Inking, With Logo)', 'category' => 'Stamps & Seals', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 2000, 'material' => null],
            ['name' => 'Stamp (Self Inking, No Dates)', 'category' => 'Stamps & Seals', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 1500, 'material' => null],
            ['name' => 'Stamp (Wooden)', 'category' => 'Stamps & Seals', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 500, 'material' => null],
            ['name' => 'Heavy Reflector', 'category' => 'Reflectors', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 600, 'material' => null],
            ['name' => 'Reflector Medium Heavy', 'category' => 'Reflectors', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 350, 'material' => null],
            ['name' => 'Reflector Light', 'category' => 'Reflectors', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 250, 'material' => null],
            ['name' => 'Magic Mug', 'category' => 'Mugs', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 800, 'material' => null],
            ['name' => 'Normal White Mugs', 'category' => 'Mugs', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 400, 'material' => null],
            ['name' => 'Stanley Mug', 'category' => 'Mugs', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 2000, 'material' => null],
            ['name' => 'Thermal Mug', 'category' => 'Mugs', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 900, 'material' => null],
            ['name' => 'Tone Mug Two Colour', 'category' => 'Mugs', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 500, 'material' => null],
            ['name' => 'Metallic Bottle 750ml', 'category' => 'Bottles', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 1000, 'material' => null],
            ['name' => 'Metallic Bottle Sub 750ml', 'category' => 'Bottles', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 850, 'material' => null],
            ['name' => 'Metallic Bottle Sub 500ml', 'category' => 'Bottles', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 650, 'material' => null],
            ['name' => 'Water Bottle Plastic UV', 'category' => 'Bottles', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 950, 'material' => null],
            ['name' => 'Polo Embroidery', 'category' => 'T-Shirts', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 1000, 'material' => null],
            ['name' => 'Polo Imported T-Shirts', 'category' => 'T-Shirts', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 800, 'material' => null],
            ['name' => 'Polo T-Shirt (Embroidery)', 'category' => 'T-Shirts', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 1000, 'material' => null],
            ['name' => 'Polo T-Shirt Printed', 'category' => 'T-Shirts', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 800, 'material' => null],
            ['name' => 'Roundneck Imported Printed', 'category' => 'T-Shirts', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 700, 'material' => null],
            ['name' => 'Roundneck T-Shirts Heavy Printed', 'category' => 'T-Shirts', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 800, 'material' => null],
            ['name' => 'Roundneck T-Shirts Printed', 'category' => 'T-Shirts', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 600, 'material' => null],
            ['name' => 'T-Shirts (Polyester)', 'category' => 'T-Shirts', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 250, 'material' => null],
            ['name' => 'Printed Pens', 'category' => 'Pens', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 50, 'material' => null],
            ['name' => 'Printed Pens (Executive)', 'category' => 'Pens', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 200, 'material' => null],
            ['name' => 'Bed Runners', 'category' => 'Other', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 2500, 'material' => null],
            ['name' => 'Sublimation Printing', 'category' => 'Sub Printing', 'order_item_type' => 'dtf_print', 'unit' => 'piece', 'default_unit_price' => 50, 'material' => null],
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
