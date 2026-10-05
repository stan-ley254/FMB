<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InkStock;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockCatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_material_creation_records_starting_quantity_as_a_purchase_movement(): void
    {
        $this->post(route('stock.materials.store'), [
            'name' => 'New Banner Roll',
            'category' => 'banner',
            'unit' => 'rolls',
            'machine' => 'large_format',
            'starting_quantity' => 4,
        ])->assertRedirect(route('stock.index'));

        $material = Material::where('name', 'New Banner Roll')->firstOrFail();
        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'quantity_remaining' => 4,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'material_id' => $material->id,
            'type' => 'purchase',
            'quantity' => 4,
            'notes' => 'Starting stock.',
        ]);
    }

    public function test_material_edit_changes_its_name_and_inactive_material_remains_visible_in_history(): void
    {
        $material = $this->createMaterial('Old Banner');
        StockMovement::create([
            'material_id' => $material->id,
            'type' => 'purchase',
            'quantity' => 2,
            'notes' => 'Initial purchase',
        ]);

        $this->patch(route('stock.materials.update', $material), [
            'name' => 'Renamed Banner',
            'category' => 'banner',
            'unit' => 'rolls',
            'machine' => 'large_format',
            'is_active' => '0',
        ])->assertRedirect(route('stock.index'));

        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'name' => 'Renamed Banner',
            'is_active' => false,
        ]);

        $this->get(route('stock.index'))
            ->assertSee('Renamed Banner')
            ->assertSee('Inactive');
        $this->get(route('stock.history', ['item' => 'material:'.$material->id]))
            ->assertSee('Renamed Banner')
            ->assertSee('Inactive');
        $this->get(route('expenses.create'))
            ->assertDontSee('Renamed Banner');
        $this->get(route('orders.create'))
            ->assertDontSee('Renamed Banner');
    }

    public function test_material_quantity_correction_records_the_signed_difference_in_stock_history(): void
    {
        $material = $this->createMaterial('Banner Roll', 7);

        $this->patch(route('stock.materials.update', $material), [
            'name' => 'Banner Roll',
            'category' => 'banner',
            'unit' => 'rolls',
            'machine' => 'large_format',
            'is_active' => '1',
            'quantity_remaining' => 99,
        ])->assertRedirect(route('stock.index'));

        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'quantity_remaining' => 99,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'material_id' => $material->id,
            'type' => 'purchase',
            'quantity' => 92,
            'notes' => 'Manual correction via edit form.',
        ]);
    }

    public function test_saving_an_unchanged_material_quantity_does_not_create_a_zero_movement(): void
    {
        $material = $this->createMaterial('Unchanged Banner', 7);

        $this->patch(route('stock.materials.update', $material), [
            'name' => 'Unchanged Banner',
            'category' => 'banner',
            'unit' => 'rolls',
            'machine' => 'large_format',
            'is_active' => '1',
            'quantity_remaining' => 7,
        ])->assertRedirect(route('stock.index'));

        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'quantity_remaining' => 7,
        ]);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_material_with_an_order_item_reference_is_deactivated_instead_of_deleted(): void
    {
        $material = $this->createMaterial('Ordered Banner');
        $customer = Customer::create(['name' => 'Catalog Test Customer']);
        $order = Order::create(['customer_id' => $customer->id]);
        OrderItem::create([
            'order_id' => $order->id,
            'item_type' => 'banner',
            'material_id' => $material->id,
            'quantity_or_meters' => 1,
            'unit_price' => 10,
            'subtotal' => 10,
        ]);

        $this->delete(route('stock.materials.destroy', $material))
            ->assertRedirect(route('stock.index'))
            ->assertSessionHas('success', 'Ordered Banner deactivated because it has history.');

        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'is_active' => false,
        ]);
        $this->get(route('stock.materials.edit', $material))
            ->assertSee('Has historical references; deactivate instead of deleting.')
            ->assertDontSee('Delete material');
    }

    public function test_material_without_historical_references_can_be_hard_deleted(): void
    {
        $material = $this->createMaterial('Unused Material');

        $this->delete(route('stock.materials.destroy', $material))
            ->assertRedirect(route('stock.index'))
            ->assertSessionHas('success', 'Unused Material deleted.');

        $this->assertDatabaseMissing('materials', ['id' => $material->id]);
    }

    public function test_ink_creation_offers_only_unconfigured_combinations_and_audits_starting_quantity(): void
    {
        InkStock::create([
            'machine' => 'large_format',
            'color' => 'cyan',
            'unit' => 'bottles',
            'quantity_remaining' => 0,
        ]);

        $this->get(route('stock.inks.create'))
            ->assertSee('DTF Printer — Magenta')
            ->assertDontSee('Large Format (I3200) — Cyan');

        $this->post(route('stock.inks.store'), [
            'combination' => 'dtf|magenta',
            'starting_quantity' => 3,
        ])->assertRedirect(route('stock.index'));

        $inkStock = InkStock::where('machine', 'dtf')->where('color', 'magenta')->firstOrFail();
        $this->assertDatabaseHas('ink_stocks', [
            'id' => $inkStock->id,
            'unit' => 'bottles',
            'quantity_remaining' => 3,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'ink_stock_id' => $inkStock->id,
            'type' => 'purchase',
            'quantity' => 3,
            'notes' => 'Starting stock.',
        ]);

        $this->post(route('stock.inks.store'), [
            'combination' => 'dtf|magenta',
            'starting_quantity' => 1,
        ])->assertSessionHasErrors('combination');
        $this->assertDatabaseCount('ink_stocks', 2);
    }

    public function test_material_with_stock_movement_is_deactivated_instead_of_deleted(): void
    {
        $material = $this->createMaterial('Restocked Material', 2);
        StockMovement::create([
            'material_id' => $material->id,
            'type' => 'purchase',
            'quantity' => 2,
        ]);

        $this->delete(route('stock.materials.destroy', $material))
            ->assertRedirect(route('stock.index'));

        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'is_active' => false,
        ]);
    }

    public function test_ink_stock_with_history_is_deactivated_and_remains_visible_in_stock_history(): void
    {
        $inkStock = InkStock::create([
            'machine' => 'dtf',
            'color' => 'cyan',
            'unit' => 'bottles',
            'quantity_remaining' => 1,
        ]);
        StockMovement::create([
            'ink_stock_id' => $inkStock->id,
            'type' => 'purchase',
            'quantity' => 1,
            'notes' => 'Initial purchase',
        ]);

        $this->delete(route('stock.inks.destroy', $inkStock))
            ->assertRedirect(route('stock.index'));

        $this->assertDatabaseHas('ink_stocks', [
            'id' => $inkStock->id,
            'is_active' => false,
        ]);
        $this->get(route('stock.index'))
            ->assertSee('DTF Printer Cyan')
            ->assertSee('Inactive');
        $this->get(route('stock.history', ['item' => 'ink:'.$inkStock->id]))
            ->assertSee('Cyan')
            ->assertSee('Inactive');
        $this->get(route('expenses.create'))
            ->assertDontSee('DTF Printer Cyan');
    }

    public function test_ink_stock_without_historical_references_can_be_hard_deleted(): void
    {
        $inkStock = InkStock::create([
            'machine' => 'dtf',
            'color' => 'cyan',
            'unit' => 'bottles',
            'quantity_remaining' => 0,
        ]);

        $this->delete(route('stock.inks.destroy', $inkStock))
            ->assertRedirect(route('stock.index'));

        $this->assertDatabaseMissing('ink_stocks', ['id' => $inkStock->id]);
    }

    public function test_ink_identity_cannot_be_changed_on_edit(): void
    {
        $inkStock = InkStock::create([
            'machine' => 'dtf',
            'color' => 'cyan',
            'unit' => 'bottles',
            'quantity_remaining' => 5,
        ]);

        $this->patch(route('stock.inks.update', $inkStock), [
            'is_active' => '0',
            'machine' => 'large_format',
            'color' => 'magenta',
        ])->assertSessionHasErrors(['machine', 'color']);

        $this->assertDatabaseHas('ink_stocks', [
            'id' => $inkStock->id,
            'machine' => 'dtf',
            'color' => 'cyan',
            'quantity_remaining' => 5,
            'is_active' => true,
        ]);
    }

    public function test_ink_quantity_correction_records_a_negative_difference(): void
    {
        $inkStock = InkStock::create([
            'machine' => 'dtf',
            'color' => 'cyan',
            'unit' => 'bottles',
            'quantity_remaining' => 5,
        ]);

        $this->patch(route('stock.inks.update', $inkStock), [
            'is_active' => '1',
            'quantity_remaining' => 3,
        ])->assertRedirect(route('stock.index'));

        $this->assertDatabaseHas('ink_stocks', [
            'id' => $inkStock->id,
            'quantity_remaining' => 3,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'ink_stock_id' => $inkStock->id,
            'type' => 'purchase',
            'quantity' => -2,
            'notes' => 'Manual correction via edit form.',
        ]);
    }

    public function test_ink_active_state_can_be_edited_without_changing_its_identity(): void
    {
        $inkStock = InkStock::create([
            'machine' => 'dtf',
            'color' => 'cyan',
            'unit' => 'bottles',
            'quantity_remaining' => 5,
        ]);

        $this->patch(route('stock.inks.update', $inkStock), [
            'is_active' => '0',
        ])->assertRedirect(route('stock.index'));

        $this->assertDatabaseHas('ink_stocks', [
            'id' => $inkStock->id,
            'machine' => 'dtf',
            'color' => 'cyan',
            'quantity_remaining' => 5,
            'is_active' => false,
        ]);
    }

    public function test_inactive_inventory_is_rejected_when_submitted_for_order_or_stock_expense(): void
    {
        $material = $this->createMaterial('Inactive Banner', 4, active: false);
        $customer = Customer::create(['name' => 'Catalog Test Customer']);

        $this->post(route('orders.store'), [
            'customer_id' => $customer->id,
            'items' => [[
                'item_type' => 'banner',
                'material_id' => $material->id,
                'quantity_or_meters' => 1,
                'unit_price' => 10,
            ]],
        ])->assertSessionHasErrors('items.0.material_id');

        $this->post(route('expenses.store'), [
            'category' => 'banner_material',
            'item_description' => 'Inactive banner restock',
            'quantity_or_size' => '2',
            'amount' => '20',
            'payment_method' => 'cash',
            'adds_to_stock' => '1',
            'stock_type' => 'material',
            'stock_item_id' => $material->id,
        ])->assertSessionHasErrors('stock_item_id');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    private function createMaterial(string $name, int $quantity = 0, bool $active = true): Material
    {
        return Material::create([
            'name' => $name,
            'category' => 'banner',
            'unit' => 'rolls',
            'machine' => 'large_format',
            'quantity_remaining' => $quantity,
            'is_active' => $active,
        ]);
    }
}
