<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderArtwork;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_with_banner_and_shop_sourced_garments_does_not_deduct_stock_and_saves_artworks(): void
    {
        $this->seed();
        Storage::fake('local');
        $customer = Customer::where('name', 'Sample Company')->firstOrFail();
        $banner = Material::where('name', 'Banner 1M')->firstOrFail();
        $shirts = Material::where('name', 'Blank T-Shirt')->firstOrFail();
        $banner->update(['quantity_remaining' => 5]);
        $shirts->update(['quantity_remaining' => 10]);

        $response = $this->post(route('orders.store'), [
            'customer_id' => $customer->id,
            'is_company_job' => '1',
            'items' => [
                [
                    'item_type' => 'banner',
                    'material_id' => $banner->id,
                    'quantity_or_meters' => 2,
                    'unit_price' => 15,
                    'discount' => 1,
                    'artworks' => [UploadedFile::fake()->image('banner-proof.png')],
                ],
                [
                    'item_type' => 'dtf_garment',
                    'material_id' => $shirts->id,
                    'quantity_or_meters' => 3,
                    'unit_price' => 5,
                    'garment_sourced_by_shop' => '1',
                    'artworks' => [UploadedFile::fake()->create('shirt-design.pdf', 10, 'application/pdf')],
                ],
            ],
        ]);

        $order = Order::firstOrFail();
        $response->assertRedirect(route('orders.show', $order));
        $this->assertSame('44.00', $order->total_amount);
        $this->assertDatabaseHas('materials', ['id' => $banner->id, 'quantity_remaining' => 5]);
        $this->assertDatabaseHas('materials', ['id' => $shirts->id, 'quantity_remaining' => 10]);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('order_artworks', 2);
        $this->assertDatabaseHas('order_artworks', ['purpose' => 'proof', 'file_type' => 'image']);
        $this->assertDatabaseHas('order_artworks', ['purpose' => 'design', 'file_type' => 'pdf']);
        OrderArtwork::pluck('file_path')->each(fn (string $path) => Storage::disk('local')->assertExists($path));
    }

    public function test_order_can_create_a_walk_in_customer_without_deducting_customer_supplied_garment_stock(): void
    {
        $this->seed();

        $response = $this->post(route('orders.store'), [
            'new_customer' => [
                'name' => 'Walk-in Order Customer',
                'phone' => '0711000000',
                'is_walk_in' => '1',
            ],
            'items' => [[
                'item_type' => 'dtf_garment',
                'quantity_or_meters' => 2,
                'unit_price' => 8,
            ]],
        ]);

        $customer = Customer::where('name', 'Walk-in Order Customer')->firstOrFail();
        $order = Order::firstOrFail();
        $response->assertRedirect(route('orders.show', $order));
        $this->assertTrue($customer->is_walk_in);
        $this->assertSame($customer->id, $order->customer_id);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'item_type' => 'dtf_garment',
            'material_id' => null,
            'garment_sourced_by_shop' => false,
        ]);
    }

    public function test_order_quantities_are_not_limited_by_storage_stock(): void
    {
        $this->seed();
        $banner = Material::where('name', 'Banner 1M')->firstOrFail();
        $banner->update(['quantity_remaining' => 1]);
        $customer = Customer::where('name', 'Sample Company')->firstOrFail();

        $response = $this->from(route('orders.create'))->post(route('orders.store'), [
            'customer_id' => $customer->id,
            'items' => [[
                'item_type' => 'banner',
                'material_id' => $banner->id,
                'quantity_or_meters' => 2,
                'unit_price' => 15,
            ]],
        ]);

        $order = Order::firstOrFail();
        $response->assertRedirect(route('orders.show', $order));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseHas('materials', ['id' => $banner->id, 'quantity_remaining' => 1]);
    }

    public function test_order_list_filters_by_status_date_and_customer(): void
    {
        $customer = Customer::create(['name' => 'Filter Match']);
        $matchingOrder = Order::create([
            'customer_id' => $customer->id,
            'status' => 'ready',
            'total_amount' => 20,
            'created_at' => '2026-09-15 12:00:00',
        ]);
        Order::create([
            'customer_id' => Customer::create(['name' => 'Other Customer'])->id,
            'status' => 'pending',
            'total_amount' => 10,
            'created_at' => '2026-08-01 12:00:00',
        ]);

        $response = $this->get(route('orders.index', [
            'status' => 'ready',
            'from' => '2026-09-01',
            'to' => '2026-09-30',
            'customer' => 'Filter Match',
        ]));

        $response->assertOk()
            ->assertSee('Order')
            ->assertSee('Filter Match')
            ->assertSee('#'.$matchingOrder->id)
            ->assertDontSee('Other Customer');
    }

    public function test_order_status_and_payment_can_be_updated_and_overpayment_is_rejected(): void
    {
        $customer = Customer::create(['name' => 'Payment Customer']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'total_amount' => 50,
        ]);

        $this->patch(route('orders.update', $order), [
            'status' => 'in_production',
            'amount_paid' => 20,
            'payment_method' => 'mpesa',
        ])->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'in_production',
            'amount_paid' => 20,
            'payment_method' => 'mpesa',
        ]);

        $this->from(route('orders.show', $order))->patch(route('orders.update', $order), [
            'status' => 'ready',
            'amount_paid' => 51,
            'payment_method' => 'cash',
        ])->assertRedirect(route('orders.show', $order))
            ->assertSessionHasErrors('amount_paid');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'in_production']);
    }

    public function test_completing_an_order_creates_one_historical_sale_snapshot(): void
    {
        $customer = Customer::create(['name' => 'Sale Customer']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'ready',
            'total_amount' => 29,
        ]);
        $order->items()->create([
            'item_type' => 'banner',
            'quantity_or_meters' => 2,
            'unit_price' => 15,
            'discount' => 1,
            'subtotal' => 29,
        ]);

        $payload = [
            'status' => 'completed',
            'amount_paid' => 20,
            'payment_method' => 'mpesa',
        ];

        $this->patch(route('orders.update', $order), $payload)->assertRedirect();
        $this->patch(route('orders.update', $order), $payload)->assertRedirect();

        $this->assertDatabaseCount('sales', 1);
        $sale = Sale::firstOrFail();
        $this->assertSame($order->id, $sale->order_id);
        $this->assertSame('29.00', $sale->total_amount);
        $this->assertSame('20.00', $sale->amount_paid);
        $this->assertSame('banner', $sale->items_snapshot[0]['item_type']);
        $this->assertSame('2.000', $sale->items_snapshot[0]['quantity']);
    }
}
