<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\InkStock;
use App\Models\Material;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $materials = [
            ['name' => 'Banner 1M', 'category' => 'banner', 'unit' => 'rolls', 'machine' => 'large_format', 'quantity_remaining' => 2],
            ['name' => 'Banner 1.2M', 'category' => 'banner', 'unit' => 'rolls', 'machine' => 'large_format', 'quantity_remaining' => 2],
            ['name' => 'Banner 1.4M', 'category' => 'banner', 'unit' => 'rolls', 'machine' => 'large_format', 'quantity_remaining' => 2],
            ['name' => 'Banner 2M', 'category' => 'banner', 'unit' => 'rolls', 'machine' => 'large_format', 'quantity_remaining' => 1],
            ['name' => 'Sertine 1M', 'category' => 'sertine', 'unit' => 'rolls', 'machine' => 'large_format', 'quantity_remaining' => 2],
            ['name' => 'Sticker 1M', 'category' => 'sticker', 'unit' => 'rolls', 'machine' => 'large_format', 'quantity_remaining' => 5],
            ['name' => 'DTF Film', 'category' => 'dtf-consumable', 'unit' => 'rolls', 'machine' => 'dtf', 'quantity_remaining' => 3],
            ['name' => 'TPU Powder', 'category' => 'dtf-consumable', 'unit' => 'sachets', 'machine' => 'dtf', 'quantity_remaining' => 3],
            ['name' => 'Blank T-Shirt', 'category' => 'garment', 'unit' => 'pieces', 'machine' => null, 'quantity_remaining' => 10],
        ];

        foreach ($materials as $material) {
            $quantity = $material['quantity_remaining'];
            unset($material['quantity_remaining']);
            $record = Material::firstOrCreate(
                ['name' => $material['name']],
                $material + ['quantity_remaining' => $quantity],
            );

            if ($record->unit !== $material['unit']) {
                $record->update(['unit' => $material['unit']]);
            }
        }

        foreach (['large_format', 'dtf'] as $machine) {
            foreach (['cyan', 'magenta', 'yellow', 'black', 'white'] as $color) {
                InkStock::firstOrCreate(
                    ['machine' => $machine, 'color' => $color],
                    ['unit' => 'bottles', 'quantity_remaining' => 5],
                );
            }
        }

        foreach ([
            ['name' => 'Walk-in Customer', 'is_walk_in' => true],
            ['name' => 'Sample Company', 'is_walk_in' => false],
            ['name' => 'Sample Customer', 'is_walk_in' => false],
        ] as $customer) {
            Customer::firstOrCreate(['name' => $customer['name']], $customer);
        }
    }
}
