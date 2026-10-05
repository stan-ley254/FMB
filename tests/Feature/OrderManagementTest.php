<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
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
        $bannerCatalogItem = CatalogItem::where('name', 'Banner 1M')->firstOrFail();
        $garmentCatalogItem = CatalogItem::where('name', 'T-shirt Branding (Shop-sourced)')->firstOrFail();
        $banner = Material::where('name', 'Banner 1M')->firstOrFail();
        $shirts = Material::where('name', 'Blank T-Shirt')->firstOrFail();
        $banner->update(['quantity_remaining' => 5]);
        $shirts->update(['quantity_remaining' => 10]);

        $response = $this->post(route('orders.store'), [
            'customer_id' => $customer->id,
            'is_company_job' => '1',
            'items' => [
                [
                    'catalog_item_id' => $bannerCatalogItem->id,
                    'quantity_or_meters' => 2,
                    'unit_price' => 15,
                    'discount' => 1,
                    'artworks' => [UploadedFile::fake()->image('banner-proof.png')],
                ],
                [
                    'catalog_item_id' => $garmentCatalogItem->id,
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
        $customerSuppliedGarment = CatalogItem::where('name', 'T-shirt Branding (Customer-supplied)')->firstOrFail();

        $response = $this->post(route('orders.store'), [
            'new_customer' => [
                'name' => 'Walk-in Order Customer',
                'phone' => '0711000000',
                'is_walk_in' => '1',
            ],
            'items' => [[
                'catalog_item_id' => $customerSuppliedGarment->id,
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
        $bannerCatalogItem = CatalogItem::where('name', 'Banner 1M')->firstOrFail();
        $banner = Material::where('name', 'Banner 1M')->firstOrFail();
        $banner->update(['quantity_remaining' => 1]);
        $customer = Customer::where('name', 'Sample Company')->firstOrFail();

        $response = $this->from(route('orders.create'))->post(route('orders.store'), [
            'customer_id' => $customer->id,
            'items' => [[
                'catalog_item_id' => $bannerCatalogItem->id,
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

    public function test_company_order_can_mix_catalog_categories_artworks_and_snapshot_them_on_completion(): void
    {
        $this->seed();
        Storage::fake('local');
        $customer = Customer::where('name', 'Sample Company')->firstOrFail();
        $banner = CatalogItem::where('name', 'Banner 1M')->firstOrFail();
        $mug = CatalogItem::where('name', 'Magic Mug')->firstOrFail();
        $garment = CatalogItem::where('name', 'T-shirt Branding (Shop-sourced)')->firstOrFail();
        $blankShirt = Material::where('name', 'Blank T-Shirt')->firstOrFail();

        $this->get(route('orders.create'))
            ->assertSee('Banner 1M · 400.00 / meter')
            ->assertSee('Magic Mug · 800.00 / piece')
            ->assertSee('T-shirt Branding (Shop-sourced) · 800.00 / piece')
            ->assertDontSee('name="items[__INDEX__][item_type]"', false);

        $this->post(route('orders.store'), [
            'customer_id' => $customer->id,
            'is_company_job' => '1',
            'due_date' => '2026-10-12',
            'notes' => 'Mixed catalog company order',
            'items' => [
                [
                    'catalog_item_id' => $banner->id,
                    'quantity_or_meters' => 2,
                    'discount' => 50,
                    'artworks' => [UploadedFile::fake()->image('banner.png')],
                ],
                [
                    'catalog_item_id' => $mug->id,
                    'quantity_or_meters' => 2,
                    'artworks' => [UploadedFile::fake()->create('mug-design.pdf', 10, 'application/pdf')],
                ],
                [
                    'catalog_item_id' => $garment->id,
                    'quantity_or_meters' => 3,
                    'garment_sourced_by_shop' => '1',
                    'artworks' => [UploadedFile::fake()->image('shirt-proof.png')],
                ],
            ],
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame('4750.00', $order->total_amount);
        $this->assertTrue($order->is_company_job);
        $this->assertSame('2026-10-12', $order->due_date->toDateString());
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'catalog_item_id' => $banner->id,
            'item_type' => 'banner',
            'material_id' => $banner->material_id,
            'catalog_item_unit' => 'meter',
            'unit_price' => 400,
            'discount' => 50,
            'subtotal' => 750,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'catalog_item_id' => $mug->id,
            'item_type' => 'mugs',
            'material_id' => null,
            'catalog_item_unit' => 'piece',
            'unit_price' => 800,
            'subtotal' => 1600,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'catalog_item_id' => $garment->id,
            'item_type' => 'dtf_garment',
            'material_id' => $blankShirt->id,
            'garment_sourced_by_shop' => true,
            'catalog_item_unit' => 'piece',
            'unit_price' => 800,
            'subtotal' => 2400,
        ]);

        $orderItems = $order->items()->orderBy('id')->get();
        $this->assertDatabaseCount('order_artworks', 3);
        $this->assertDatabaseHas('order_artworks', ['order_item_id' => $orderItems[0]->id, 'purpose' => 'proof']);
        $this->assertDatabaseHas('order_artworks', ['order_item_id' => $orderItems[1]->id, 'purpose' => 'design']);
        $this->assertDatabaseHas('order_artworks', ['order_item_id' => $orderItems[2]->id, 'purpose' => 'proof']);
        $this->get(route('orders.show', $order))
            ->assertSee('Banner 1M')
            ->assertSee('Magic Mug')
            ->assertSee('T-shirt Branding (Shop-sourced)')
            ->assertSee('Designs &amp; production proofs', false);

        $this->patch(route('orders.update', $order), [
            'status' => 'completed',
            'amount_paid' => 0,
            'payment_method' => 'cash',
        ])->assertRedirect(route('orders.show', $order));

        $sale = Sale::where('order_id', $order->id)->firstOrFail();
        $this->assertSame(3, count($sale->items_snapshot));
        $this->assertSame('banner', $sale->items_snapshot[0]['item_type']);
        $this->assertSame('banner', $sale->items_snapshot[0]['category']);
        $this->assertSame($banner->material_id, $sale->items_snapshot[0]['material_id']);
        $this->assertSame('mugs', $sale->items_snapshot[1]['item_type']);
        $this->assertSame('Mugs', $sale->items_snapshot[1]['category']);
        $this->assertNull($sale->items_snapshot[1]['material_id']);
        $this->assertSame('dtf_garment', $sale->items_snapshot[2]['item_type']);
        $this->assertSame('garment', $sale->items_snapshot[2]['category']);
        $this->assertSame($blankShirt->id, $sale->items_snapshot[2]['material_id']);
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
        $material = Material::create([
            'name' => 'Snapshot Banner',
            'category' => 'banner',
            'unit' => 'rolls',
            'quantity_remaining' => 2,
            'machine' => 'large_format',
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'ready',
            'total_amount' => 29,
        ]);
        $order->items()->create([
            'item_type' => 'banner',
            'material_id' => $material->id,
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
        $this->assertSame($material->id, $sale->items_snapshot[0]['material_id']);
        $this->assertSame('Snapshot Banner', $sale->items_snapshot[0]['material_name']);
        $this->assertSame('2.000', $sale->items_snapshot[0]['quantity']);
    }
}
