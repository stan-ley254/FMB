<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_index_shows_sale_details_and_links_to_its_order(): void
    {
        $sale = $this->createSale(
            'Northstar Press',
            '2026-09-10',
            '50.00',
            '30.00',
            'bank',
            'Meridian Bank',
            [[
                'item_type' => 'banner',
                'material_name' => 'Banner 1.4M',
                'quantity' => '2.000',
            ]],
        );

        $this->get(route('sales.index'))
            ->assertOk()
            ->assertSee('Sep 10, 2026')
            ->assertSee('Northstar Press')
            ->assertSee('2 m Banner 1.4M')
            ->assertSee('50.00')
            ->assertSee('30.00')
            ->assertSee('BANK')
            ->assertSee('Meridian Bank')
            ->assertSee(route('orders.show', $sale->order_id), false);
    }

    public function test_sales_index_filters_by_completion_date_and_customer_name(): void
    {
        $this->createSale('Northstar Press', '2026-09-10', '50.00', '30.00', 'cash', null, [
            ['item_type' => 'banner', 'material_name' => 'Banner 1.4M', 'quantity' => '2.000'],
        ]);
        $this->createSale('Northstar Press', '2026-08-31', '20.00', '20.00', 'cash', null, [
            ['item_type' => 'sticker', 'material_name' => 'Sticker Roll', 'quantity' => '1.000'],
        ]);
        $this->createSale('South Press', '2026-09-12', '35.00', '35.00', 'mpesa', null, [
            ['item_type' => 'sertine', 'material_name' => 'Sertine', 'quantity' => '3.000'],
        ]);

        $this->get(route('sales.index', [
            'from' => '2026-09-01',
            'to' => '2026-09-30',
            'customer' => 'North',
        ]))
            ->assertOk()
            ->assertSee('Northstar Press')
            ->assertSee('50.00')
            ->assertDontSee('20.00')
            ->assertDontSee('South Press')
            ->assertDontSee('35.00');
    }

    public function test_sales_index_filters_by_material_name_and_item_type(): void
    {
        $this->createSale('Matching Customer', '2026-09-10', '50.00', '30.00', 'cash', null, [
            ['item_type' => 'banner', 'material_name' => 'Banner 1.4M', 'quantity' => '2.000'],
        ]);
        $this->createSale('Different Material', '2026-09-11', '20.00', '20.00', 'cash', null, [
            ['item_type' => 'banner', 'material_name' => 'Banner 1.2M', 'quantity' => '1.000'],
        ]);
        $this->createSale('Different Type', '2026-09-12', '35.00', '35.00', 'cash', null, [
            ['item_type' => 'sticker', 'material_name' => 'Banner 1.4M', 'quantity' => '3.000'],
        ]);

        $this->get(route('sales.index', [
            'material' => 'Banner 1.4M',
            'item_type' => 'banner',
        ]))
            ->assertOk()
            ->assertSee('Matching Customer')
            ->assertSee('50.00')
            ->assertDontSee('Different Material')
            ->assertDontSee('Different Type')
            ->assertDontSee('35.00');
    }

    public function test_sales_index_material_search_matches_only_material_names_in_the_snapshot(): void
    {
        $this->createSale('Matching Material', '2026-09-10', '50.00', '50.00', 'cash', null, [
            ['item_type' => 'banner', 'material_name' => 'Banner 1.2M', 'catalog_item_name' => 'Banner 1.4M', 'quantity' => '2.000'],
        ]);
        $this->createSale('Different Material', '2026-09-11', '20.00', '20.00', 'cash', null, [
            ['item_type' => 'banner', 'material_name' => 'Banner 1.4M', 'quantity' => '1.000'],
        ]);

        $this->get(route('sales.index', ['material' => 'Banner 1.4M']))
            ->assertOk()
            ->assertSee('Different Material')
            ->assertDontSee('Matching Material');
    }

    public function test_sales_index_combined_filters_and_usage_summary_are_limited_to_matching_snapshot_lines(): void
    {
        $this->createSale('Northstar Press', '2026-09-10', '60.00', '60.00', 'cash', null, [
            ['item_type' => 'banner', 'material_name' => 'Banner 1.4M', 'quantity' => '2.000'],
            ['item_type' => 'dtf_print', 'material_name' => 'DTF Film', 'quantity' => '3.000'],
        ]);
        $this->createSale('Northstar Press', '2026-08-31', '30.00', '30.00', 'cash', null, [
            ['item_type' => 'banner', 'material_name' => 'Banner 1.4M', 'quantity' => '4.000'],
        ]);
        $this->createSale('South Press', '2026-09-12', '50.00', '50.00', 'cash', null, [
            ['item_type' => 'banner', 'material_name' => 'Banner 1.4M', 'quantity' => '5.000'],
        ]);
        $this->createSale('Northstar Press', '2026-09-13', '70.00', '70.00', 'cash', null, [
            ['item_type' => 'banner', 'material_name' => 'Banner 1.2M', 'quantity' => '7.000'],
        ]);

        $this->get(route('sales.index', [
            'from' => '2026-09-01',
            'to' => '2026-09-30',
            'customer' => 'Northstar',
            'material' => 'Banner 1.4M',
            'item_type' => 'banner',
        ]))
            ->assertOk()
            ->assertSee('2 m Banner 1.4M')
            ->assertDontSee('South Press')
            ->assertViewHas('usageSummary', [[
                'item_type' => 'banner',
                'name' => 'Banner 1.4M',
                'quantity' => 2.0,
                'unit' => 'meter',
            ]]);
    }

    public function test_sales_index_aggregates_meter_and_piece_quantities_from_sale_snapshots(): void
    {
        $this->createSale('Customer One', '2026-09-10', '100.00', '100.00', 'cash', null, [
            ['item_type' => 'banner', 'material_name' => 'Banner 1M', 'quantity' => '2.000', 'unit' => 'meter'],
            ['item_type' => 'dtf_print', 'material_name' => null, 'catalog_item_name' => 'DTF NoCut Printing', 'quantity' => '1.500', 'unit' => 'meter'],
            ['item_type' => 'dtf_print', 'material_name' => null, 'catalog_item_name' => 'Magic Mug', 'quantity' => '2.000', 'unit' => 'piece'],
        ]);
        $this->createSale('Customer Two', '2026-09-12', '100.00', '100.00', 'cash', null, [
            ['item_type' => 'banner', 'material_name' => 'Banner 1M', 'quantity' => '3.000', 'unit' => 'meter'],
            ['item_type' => 'dtf_print', 'material_name' => null, 'catalog_item_name' => 'DTF NoCut Printing', 'quantity' => '2.500', 'unit' => 'meter'],
        ]);

        $this->get(route('sales.index'))
            ->assertViewHas('usageSummary', [
                [
                    'item_type' => 'banner',
                    'name' => 'Banner 1M',
                    'quantity' => 5.0,
                    'unit' => 'meter',
                ],
                [
                    'item_type' => 'dtf_print',
                    'name' => 'DTF NoCut Printing',
                    'quantity' => 4.0,
                    'unit' => 'meter',
                ],
                [
                    'item_type' => 'dtf_print',
                    'name' => 'Magic Mug',
                    'quantity' => 2.0,
                    'unit' => 'piece',
                ],
            ])
            ->assertSee('Filtered item usage')
            ->assertSee('DTF Print');
    }

    public function test_sales_export_downloads_filtered_csv_with_snapshot_and_payment_data(): void
    {
        $sale = $this->createSale(
            'North, Star Press',
            '2026-09-10',
            '50.00',
            '30.00',
            'bank',
            'Meridian Bank',
            [[
                'item_type' => 'dtf_garment',
                'material_name' => null,
                'quantity' => '1.000',
            ]],
        );
        $this->createSale('South Press', '2026-09-11', '20.00', '20.00', 'cash', null, [
            ['item_type' => 'sticker', 'material_name' => 'Sticker Roll', 'quantity' => '1.000'],
        ]);

        $response = $this->get(route('sales.export', ['customer' => 'North']));

        $response->assertDownload('sales.csv');
        $rows = array_map(
            fn (string $row): array => str_getcsv($row),
            preg_split('/\r\n|\n|\r/', trim($response->streamedContent())),
        );

        $this->assertSame([
            'Completed at',
            'Order',
            'Customer',
            'Total amount',
            'Amount paid',
            'Payment method',
            'Bank name',
            'Items',
        ], $rows[0]);
        $this->assertSame([
            '2026-09-10 00:00:00',
            (string) $sale->order_id,
            'North, Star Press',
            '50.00',
            '30.00',
            'bank',
            'Meridian Bank',
            '1 pc DTF Garment',
        ], $rows[1]);
        $this->assertCount(2, $rows);
    }

    /**
     * @param  array<int, array{item_type: string, material_name: ?string, quantity: string}>  $itemsSnapshot
     */
    private function createSale(
        string $customerName,
        string $completedAt,
        string $totalAmount,
        string $amountPaid,
        ?string $paymentMethod,
        ?string $bankName,
        array $itemsSnapshot,
    ): Sale {
        $customer = Customer::create(['name' => $customerName]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'completed',
            'total_amount' => $totalAmount,
        ]);

        return Sale::create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'total_amount' => $totalAmount,
            'amount_paid' => $amountPaid,
            'payment_method' => $paymentMethod,
            'bank_name' => $bankName,
            'completed_at' => $completedAt,
            'items_snapshot' => $itemsSnapshot,
        ]);
    }
}
