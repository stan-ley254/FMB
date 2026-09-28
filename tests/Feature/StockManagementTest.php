<?php

namespace Tests\Feature;

use App\Models\InkStock;
use App\Models\Material;
use App\Models\StockMovement;
use Illuminate\Support\Carbon;
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
            'quantity' => 12,
            'notes' => 'New roll',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'quantity_remaining' => 14,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'material_id' => $material->id,
            'ink_stock_id' => null,
            'type' => 'purchase',
            'quantity' => 12,
            'notes' => 'New roll',
        ]);
    }

    public function test_ink_top_up_is_recorded_separately_from_material_stock(): void
    {
        $this->seed();
        $ink = InkStock::where('machine', 'dtf')->where('color', 'cyan')->firstOrFail();

        $response = $this->post(route('stock.inks.add', $ink), [
            'quantity' => 2,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ink_stocks', [
            'id' => $ink->id,
            'quantity_remaining' => 7,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'material_id' => null,
            'ink_stock_id' => $ink->id,
            'type' => 'purchase',
            'quantity' => 2,
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

    public function test_material_can_be_manually_used_one_unit_and_records_usage(): void
    {
        $this->seed();
        $material = Material::where('name', 'Banner 1M')->firstOrFail();

        $this->post(route('stock.materials.use', $material))->assertRedirect();

        $this->assertDatabaseHas('materials', ['id' => $material->id, 'quantity_remaining' => 1]);
        $this->assertDatabaseHas('stock_movements', [
            'material_id' => $material->id,
            'type' => 'usage',
            'quantity' => 1,
            'related_order_id' => null,
        ]);
    }

    public function test_ink_can_be_manually_used_one_bottle_and_records_usage(): void
    {
        $this->seed();
        $ink = InkStock::where('machine', 'dtf')->where('color', 'cyan')->firstOrFail();

        $this->post(route('stock.inks.use', $ink))->assertRedirect();

        $this->assertDatabaseHas('ink_stocks', ['id' => $ink->id, 'quantity_remaining' => 4]);
        $this->assertDatabaseHas('stock_movements', [
            'ink_stock_id' => $ink->id,
            'type' => 'usage',
            'quantity' => 1,
            'related_order_id' => null,
        ]);
    }

    public function test_stock_history_filters_movements_and_shows_days_between_uses(): void
    {
        $this->seed();
        $material = Material::where('name', 'Banner 1M')->firstOrFail();

        foreach ([Carbon::parse('2026-09-20 09:00'), Carbon::parse('2026-09-23 09:00'), Carbon::parse('2026-09-28 09:00')] as $createdAt) {
            StockMovement::forceCreate([
                'material_id' => $material->id,
                'type' => 'usage',
                'quantity' => 1,
                'created_at' => $createdAt,
            ]);
        }

        StockMovement::forceCreate([
            'material_id' => $material->id,
            'type' => 'purchase',
            'quantity' => 2,
            'notes' => 'Restocked',
            'created_at' => Carbon::parse('2026-09-19 09:00'),
        ]);

        $response = $this->get(route('stock.history', [
            'item' => 'material:'.$material->id,
            'type' => 'usage',
            'from' => '2026-09-20',
            'to' => '2026-09-28',
        ]));

        $response->assertOk()
            ->assertSee('Sep 28, 2026 09:00')
            ->assertSee('5 days')
            ->assertSee('3 days')
            ->assertDontSee('Restocked');
    }

    public function test_stock_page_shows_last_used_and_restocked_timestamps(): void
    {
        $this->seed();
        $material = Material::where('name', 'Banner 1M')->firstOrFail();

        StockMovement::forceCreate([
            'material_id' => $material->id,
            'type' => 'purchase',
            'quantity' => 1,
            'created_at' => Carbon::parse('2026-09-25 09:00'),
        ]);
        StockMovement::forceCreate([
            'material_id' => $material->id,
            'type' => 'usage',
            'quantity' => 1,
            'created_at' => Carbon::parse('2026-09-28 09:00'),
        ]);

        $this->get(route('stock.index'))
            ->assertOk()
            ->assertSee('Last used:')
            ->assertSee('0 days ago')
            ->assertSee('Last restocked:');
    }
}
