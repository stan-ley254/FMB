<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Material;
use App\Models\Order;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuickSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_seed_contains_the_confirmed_print_and_garment_services(): void
    {
        $this->seed();

        $this->assertDatabaseHas('catalog_items', [
            'name' => 'Banner 1M',
            'order_item_type' => 'banner',
            'unit' => 'meter',
            'default_unit_price' => 400,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('catalog_items', [
            'name' => 'DTF NoCut Printing',
            'order_item_type' => 'dtf_print',
            'unit' => 'meter',
            'default_unit_price' => 450,
        ]);
        $this->assertDatabaseHas('catalog_items', [
            'name' => 'T-shirt Branding (Customer-supplied)',
            'order_item_type' => 'dtf_garment',
            'unit' => 'piece',
            'default_unit_price' => 800,
            'material_id' => null,
        ]);
    }

    public function test_quick_sale_creates_a_completed_order_and_sale_snapshot_without_consuming_stock(): void
    {
        $this->seed();
        Storage::fake('local');
        $customer = Customer::create([
            'name' => 'Quick Sale Customer',
            'is_walk_in' => true,
        ]);
        $material = Material::where('name', 'Banner 1M')->firstOrFail();
        $catalogItem = CatalogItem::where('name', 'Banner 1M')->firstOrFail();
        $initialQuantity = $material->quantity_remaining;

        $response = $this->post(route('quick-sales.store'), [
            'customer_id' => $customer->id,
            'items' => [[
                'catalog_item_id' => $catalogItem->id,
                'quantity' => 2,
                'unit_price' => 400,
                'discount' => 50,
            ]],
            'amount_paid' => 750,
            'payment_method' => 'cash',
        ]);

        $order = Order::firstOrFail();
        $response->assertRedirect(route('orders.show', $order));
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'customer_id' => $customer->id,
            'status' => 'completed',
            'total_amount' => 750,
            'amount_paid' => 750,
            'payment_method' => 'cash',
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'item_type' => 'banner',
            'material_id' => $material->id,
            'catalog_item_id' => $catalogItem->id,
            'catalog_item_name' => 'Banner 1M',
            'catalog_item_unit' => 'meter',
            'quantity_or_meters' => 2,
            'unit_price' => 400,
            'discount' => 50,
            'subtotal' => 750,
        ]);
        $this->assertDatabaseCount('sales', 1);
        $sale = Sale::firstOrFail();
        $this->assertSame($order->id, $sale->order_id);
        $this->assertSame('Banner 1M', $sale->items_snapshot[0]['catalog_item_name']);
        $this->assertSame('750.00', $sale->total_amount);
        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'quantity_remaining' => $initialQuantity,
        ]);
        $this->assertDatabaseCount('stock_movements', 0);

        $this->get(route('customers.show', $customer))
            ->assertSee('Order #'.$order->id)
            ->assertSee('Completed');
        $this->get(route('sales.index'))
            ->assertSee('Quick Sale Customer')
            ->assertSee('2 m Banner 1M');
    }

    public function test_quick_sale_can_create_a_walk_in_customer_and_record_optional_artwork(): void
    {
        $this->seed();
        Storage::fake('local');
        $catalogItem = CatalogItem::where('name', 'DTF NoCut Printing')->firstOrFail();

        $this->post(route('quick-sales.store'), [
            'new_customer' => [
                'name' => 'Same-day Walk-in',
                'phone' => '0712345678',
            ],
            'items' => [[
                'catalog_item_id' => $catalogItem->id,
                'quantity' => 1.5,
                'unit_price' => 450,
                'artworks' => [UploadedFile::fake()->image('design.png')],
            ]],
            'amount_paid' => 675,
            'payment_method' => 'mpesa',
        ])->assertRedirect();

        $customer = Customer::where('name', 'Same-day Walk-in')->firstOrFail();
        $order = Order::where('customer_id', $customer->id)->firstOrFail();
        $this->assertTrue($customer->is_walk_in);
        $this->assertSame('completed', $order->status);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'item_type' => 'dtf_print',
            'material_id' => null,
            'catalog_item_name' => 'DTF NoCut Printing',
            'catalog_item_unit' => 'meter',
        ]);
        $this->assertDatabaseHas('order_artworks', [
            'order_item_id' => $order->items()->value('id'),
            'purpose' => 'proof',
            'file_type' => 'image',
        ]);
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_quick_sale_rejects_payment_above_the_computed_total_without_creating_a_sale(): void
    {
        $this->seed();
        $customer = Customer::create(['name' => 'Overpayment Customer']);
        $catalogItem = CatalogItem::where('name', 'Banner 1M')->firstOrFail();

        $this->from(route('quick-sales.create'))->post(route('quick-sales.store'), [
            'customer_id' => $customer->id,
            'items' => [[
                'catalog_item_id' => $catalogItem->id,
                'quantity' => 1,
                'unit_price' => 400,
            ]],
            'amount_paid' => 401,
            'payment_method' => 'cash',
        ])->assertRedirect(route('quick-sales.create'))
            ->assertSessionHasErrors('amount_paid');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_quick_sale_hides_and_rejects_inactive_catalog_items(): void
    {
        $this->seed();
        $catalogItem = CatalogItem::where('name', 'Sticker A3')->firstOrFail();
        $catalogItem->update(['is_active' => false]);

        $this->get(route('quick-sales.create'))
            ->assertDontSee('Sticker A3');
        $this->post(route('quick-sales.store'), [
            'new_customer' => ['name' => 'Inactive Catalog Customer'],
            'items' => [[
                'catalog_item_id' => $catalogItem->id,
                'quantity' => 1,
                'unit_price' => 50,
            ]],
            'amount_paid' => 50,
            'payment_method' => 'cash',
        ])->assertSessionHasErrors('items.0.catalog_item_id');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_catalog_item_can_be_created_edited_and_deactivated(): void
    {
        $this->seed();
        $material = Material::where('name', 'Banner 1M')->firstOrFail();

        $this->post(route('catalog.store'), [
            'name' => 'Custom Banner Service',
            'category' => 'banner',
            'order_item_type' => 'banner',
            'unit' => 'meter',
            'default_unit_price' => '525.00',
            'material_id' => $material->id,
        ])->assertRedirect(route('catalog.index'));

        $catalogItem = CatalogItem::where('name', 'Custom Banner Service')->firstOrFail();
        $this->assertDatabaseHas('catalog_items', [
            'id' => $catalogItem->id,
            'default_unit_price' => 525,
            'material_id' => $material->id,
            'is_active' => true,
        ]);

        $this->patch(route('catalog.update', $catalogItem), [
            'name' => 'Custom Banner Service',
            'category' => 'banner',
            'order_item_type' => 'banner',
            'unit' => 'meter',
            'default_unit_price' => '550.00',
            'material_id' => $material->id,
            'is_active' => '0',
        ])->assertRedirect(route('catalog.index'));

        $this->assertDatabaseHas('catalog_items', [
            'id' => $catalogItem->id,
            'default_unit_price' => 550,
            'is_active' => false,
        ]);
        $this->get(route('quick-sales.create'))
            ->assertDontSee('Custom Banner Service');
        $this->get(route('catalog.index'))
            ->assertSee('Custom Banner Service')
            ->assertSee('Inactive');
    }
}
