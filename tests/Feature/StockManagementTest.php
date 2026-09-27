<?php

namespace Tests\Feature;

use App\Models\InkStock;
use App\Models\Material;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_material_top_up_changes_balance_and_records_purchase_movement(): void
    {
        $this->seed();
        $material = Material::where('name', 'Banner 1M')->firstOrFail();

        $response = $this->post(route('stock.materials.add', $material), [
            'quantity' => 12.5,
            'notes' => 'New roll',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'quantity_remaining' => 12.5,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'material_id' => $material->id,
            'ink_stock_id' => null,
            'type' => 'purchase',
            'quantity' => 12.5,
            'notes' => 'New roll',
        ]);
    }

    public function test_ink_top_up_is_recorded_separately_from_material_stock(): void
    {
        $this->seed();
        $ink = InkStock::where('machine', 'dtf')->where('color', 'cyan')->firstOrFail();

        $response = $this->post(route('stock.inks.add', $ink), [
            'quantity' => 1.25,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ink_stocks', [
            'id' => $ink->id,
            'quantity_remaining' => 1.25,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'material_id' => null,
            'ink_stock_id' => $ink->id,
            'type' => 'purchase',
            'quantity' => 1.25,
        ]);
    }

    public function test_stock_page_shows_material_categories_and_machine_scoped_ink_rows(): void
    {
        $this->seed();

        $response = $this->get(route('stock.index'));

        $response->assertOk()
            ->assertSee('Banner widths')
            ->assertSee('DTF consumables')
            ->assertSee('Large Format (I3200) Cyan')
            ->assertSee('DTF Printer Cyan');
    }
}
