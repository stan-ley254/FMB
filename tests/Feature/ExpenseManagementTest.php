<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\InkStock;
use App\Models\Material;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_form_lists_inventory_and_bank_options(): void
    {
        $this->createMaterial(5);
        InkStock::create([
            'machine' => 'dtf',
            'color' => 'cyan',
            'unit' => 'bottles',
            'quantity_remaining' => 2,
        ]);

        $this->get(route('expenses.create'))
            ->assertOk()
            ->assertSee('Expense Test Banner')
            ->assertSee('DTF Printer Cyan')
            ->assertSee('KCB Bank')
            ->assertSee('This purchase adds to stock');
    }

    public function test_recording_a_stock_expense_increments_material_and_creates_purchase_movement(): void
    {
        $material = $this->createMaterial(5);

        $response = $this->post(route('expenses.store'), [
            'category' => 'banner_material',
            'supplier_name' => 'Banner Supply Co',
            'item_description' => 'Banner roll',
            'quantity_or_size' => '3',
            'amount' => '45.50',
            'payment_method' => 'cash',
            'adds_to_stock' => '1',
            'stock_type' => 'material',
            'stock_item_id' => $material->id,
        ]);

        $expense = Expense::firstOrFail();
        $response->assertRedirect(route('expenses.edit', $expense));
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'adds_to_stock' => true,
            'related_material_id' => $material->id,
            'related_ink_stock_id' => null,
            'quantity_or_size' => '3',
        ]);
        $this->assertDatabaseHas('materials', ['id' => $material->id, 'quantity_remaining' => 8]);
        $this->assertDatabaseHas('stock_movements', [
            'material_id' => $material->id,
            'type' => 'purchase',
            'quantity' => 3,
            'notes' => "Expense #{$expense->id}: Banner roll",
        ]);

        $this->get(route('expenses.index'))
            ->assertOk()
            ->assertSee('Banner material')
            ->assertSee('Banner Supply Co')
            ->assertSee('Banner roll')
            ->assertSee('45.50')
            ->assertSee('Added: Expense Test Banner')
            ->assertSee(route('stock.history', ['item' => 'material:'.$material->id]), false);
    }

    public function test_recording_an_expense_without_a_stock_addition_does_not_create_stock_movements(): void
    {
        $this->post(route('expenses.store'), [
            'category' => 'other',
            'supplier_name' => 'Office Supplier',
            'item_description' => 'Paper and labels',
            'quantity_or_size' => '2 boxes',
            'amount' => '12.50',
            'payment_method' => 'cash',
        ])->assertRedirect(route('expenses.edit', Expense::firstOrFail()));

        $this->assertDatabaseHas('expenses', [
            'category' => 'other',
            'supplier_name' => 'Office Supplier',
            'quantity_or_size' => '2 boxes',
            'adds_to_stock' => false,
            'related_material_id' => null,
            'related_ink_stock_id' => null,
        ]);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_recording_an_ink_stock_expense_increments_ink_and_creates_purchase_movement(): void
    {
        $inkStock = InkStock::create([
            'machine' => 'dtf',
            'color' => 'cyan',
            'unit' => 'bottles',
            'quantity_remaining' => 2,
        ]);

        $this->post(route('expenses.store'), [
            'category' => 'ink',
            'item_description' => 'Cyan ink bottles',
            'quantity_or_size' => '4',
            'amount' => '80',
            'payment_method' => 'mpesa',
            'adds_to_stock' => '1',
            'stock_type' => 'ink',
            'stock_item_id' => $inkStock->id,
        ])->assertRedirect(route('expenses.edit', Expense::firstOrFail()));

        $this->assertDatabaseHas('ink_stocks', ['id' => $inkStock->id, 'quantity_remaining' => 6]);
        $this->assertDatabaseHas('stock_movements', [
            'ink_stock_id' => $inkStock->id,
            'type' => 'purchase',
            'quantity' => 4,
        ]);
    }

    public function test_deleting_a_stock_expense_reverses_stock_and_keeps_a_negative_purchase_audit_entry(): void
    {
        $material = $this->createMaterial(5);
        $expense = $this->createStockExpense($material, 3);

        $this->delete(route('expenses.destroy', $expense))
            ->assertRedirect(route('expenses.index'));

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
        $this->assertDatabaseHas('materials', ['id' => $material->id, 'quantity_remaining' => 5]);
        $this->assertDatabaseCount('stock_movements', 2);
        $this->assertDatabaseHas('stock_movements', [
            'material_id' => $material->id,
            'type' => 'purchase',
            'quantity' => -3,
            'notes' => "Reversal of expense #{$expense->id} purchase.",
        ]);
    }

    public function test_editing_expense_details_does_not_change_locked_stock_quantity_or_linkage(): void
    {
        $material = $this->createMaterial(5);
        $expense = $this->createStockExpense($material, 3);

        $this->patch(route('expenses.update', $expense), [
            'category' => 'banner_material',
            'supplier_name' => 'Updated Supplier',
            'item_description' => 'Updated description',
            'amount' => '55.00',
            'payment_method' => 'bank',
            'bank_name' => 'KCB Bank',
        ])->assertRedirect(route('expenses.edit', $expense));

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'supplier_name' => 'Updated Supplier',
            'item_description' => 'Updated description',
            'quantity_or_size' => '3',
            'amount' => '55.00',
            'payment_method' => 'bank',
            'bank_name' => 'KCB Bank',
            'adds_to_stock' => true,
            'related_material_id' => $material->id,
        ]);
        $this->assertDatabaseHas('materials', ['id' => $material->id, 'quantity_remaining' => 8]);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_expense_update_rejects_changes_to_stock_quantity_and_adds_to_stock_flag(): void
    {
        $material = $this->createMaterial(5);
        $expense = $this->createStockExpense($material, 3);

        $this->patch(route('expenses.update', $expense), [
            'category' => 'banner_material',
            'supplier_name' => 'Stock Supplier',
            'item_description' => 'Stock purchase',
            'quantity_or_size' => '1',
            'amount' => '45.50',
            'payment_method' => 'cash',
            'adds_to_stock' => '0',
        ])->assertSessionHasErrors(['quantity_or_size', 'adds_to_stock']);

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'quantity_or_size' => '3',
            'adds_to_stock' => true,
            'related_material_id' => $material->id,
        ]);
        $this->assertDatabaseHas('materials', ['id' => $material->id, 'quantity_remaining' => 8]);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_expense_index_filters_by_date_category_and_supplier(): void
    {
        $this->createExpense('ink', 'Northstar Ink', '2026-09-10', '30.00');
        $this->createExpense('ink', 'Northstar Ink', '2026-08-31', '20.00');
        $this->createExpense('powder', 'South Supply', '2026-09-12', '15.00');

        $this->get(route('expenses.index', [
            'from' => '2026-09-01',
            'to' => '2026-09-30',
            'category' => 'ink',
            'supplier' => 'North',
        ]))
            ->assertOk()
            ->assertSee('Northstar Ink')
            ->assertSee('30.00')
            ->assertDontSee('20.00')
            ->assertDontSee('South Supply')
            ->assertDontSee('15.00');
    }

    public function test_expense_export_downloads_filtered_csv_matching_the_expense_data(): void
    {
        $expense = $this->createExpense('ink', 'North, Star Supply', '2026-09-10', '30.00');
        $expense->update([
            'quantity_or_size' => '2',
            'payment_method' => 'bank',
            'bank_name' => 'KCB Bank',
            'adds_to_stock' => false,
        ]);
        $this->createExpense('powder', 'South Supply', '2026-09-11', '15.00');

        $response = $this->get(route('expenses.export', [
            'from' => '2026-09-01',
            'to' => '2026-09-30',
            'category' => 'ink',
            'supplier' => 'North',
        ]));

        $response->assertDownload('expenses.csv');
        $rows = array_map(
            fn (string $row): array => str_getcsv($row),
            preg_split('/\r\n|\n|\r/', trim($response->streamedContent())),
        );

        $this->assertSame([
            'Date',
            'Category',
            'Supplier',
            'Item description',
            'Quantity or size',
            'Amount',
            'Payment method',
            'Bank name',
            'Adds to stock',
            'Stock item',
        ], $rows[0]);
        $this->assertSame([
            '2026-09-10 00:00:00',
            'ink',
            'North, Star Supply',
            'Purchase',
            '2',
            '30.00',
            'bank',
            'KCB Bank',
            'No',
            '',
        ], $rows[1]);
        $this->assertCount(2, $rows);
    }

    public function test_stock_purchase_requires_a_valid_inventory_link_and_whole_positive_quantity(): void
    {
        $material = $this->createMaterial(5);

        $this->from(route('expenses.create'))->post(route('expenses.store'), [
            'category' => 'banner_material',
            'item_description' => 'Invalid stock purchase',
            'quantity_or_size' => '1.5',
            'amount' => '10',
            'payment_method' => 'cash',
            'adds_to_stock' => '1',
            'stock_type' => 'material',
            'stock_item_id' => $material->id,
        ])->assertRedirect(route('expenses.create'))
            ->assertSessionHasErrors('quantity_or_size');

        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseHas('materials', ['id' => $material->id, 'quantity_remaining' => 5]);
    }

    private function createMaterial(int $quantity): Material
    {
        return Material::create([
            'name' => 'Expense Test Banner',
            'category' => 'banner',
            'unit' => 'rolls',
            'quantity_remaining' => $quantity,
            'machine' => 'large_format',
        ]);
    }

    private function createStockExpense(Material $material, int $quantity): Expense
    {
        $this->post(route('expenses.store'), [
            'category' => 'banner_material',
            'supplier_name' => 'Stock Supplier',
            'item_description' => 'Stock purchase',
            'quantity_or_size' => (string) $quantity,
            'amount' => '45.50',
            'payment_method' => 'cash',
            'adds_to_stock' => '1',
            'stock_type' => 'material',
            'stock_item_id' => $material->id,
        ]);

        return Expense::firstOrFail();
    }

    private function createExpense(string $category, string $supplier, string $date, string $amount): Expense
    {
        $expense = Expense::create([
            'category' => $category,
            'supplier_name' => $supplier,
            'item_description' => 'Purchase',
            'amount' => $amount,
            'payment_method' => 'cash',
            'adds_to_stock' => false,
        ]);
        $expense->created_at = $date;
        $expense->save();

        return $expense;
    }
}
