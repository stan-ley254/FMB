<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Material;
use App\Models\Order;
use App\Models\Sale;
use Database\Seeders\CatalogItemSeeder;
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
        $artwork = $order->items()->firstOrFail()->artworks()->firstOrFail();
        Storage::disk('local')->assertExists($artwork->file_path);
        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Designs &amp; production proofs', false)
            ->assertSee('DTF NoCut Printing')
            ->assertSee(route('artworks.download', $artwork), false);
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

    public function test_catalog_seeder_adds_all_listed_items_once_without_overwriting_existing_items(): void
    {
        $this->seed();
        CatalogItem::where('name', 'Banner 1M')->update(['default_unit_price' => 999]);

        $this->seed(CatalogItemSeeder::class);

        $this->assertDatabaseCount('catalog_items', 63);
        $this->assertDatabaseHas('catalog_items', [
            'name' => 'Banner 1M',
            'default_unit_price' => 999,
            'material_id' => Material::where('name', 'Banner 1M')->value('id'),
        ]);
        $this->assertDatabaseHas('catalog_items', [
            'name' => 'A4 Duplicate 1 Color',
            'category' => 'Receipt Books',
            'order_item_type' => 'receipt_books',
            'unit' => 'piece',
            'default_unit_price' => 600,
            'material_id' => null,
        ]);
        $this->assertDatabaseHas('catalog_items', [
            'name' => 'Digital Seal',
            'category' => 'Stamps & Seals',
            'unit' => 'piece',
            'default_unit_price' => 5000,
            'material_id' => null,
        ]);
        $this->assertDatabaseHas('catalog_items', [
            'name' => 'Sublimation Printing',
            'category' => 'Sub Printing',
            'order_item_type' => 'sub_printing',
            'unit' => 'piece',
            'default_unit_price' => 50,
            'material_id' => null,
        ]);

        $this->assertSame(
            0,
            CatalogItem::query()
                ->whereIn('category', [
                    'Receipt Books',
                    'Paper Printing',
                    'Caps',
                    'Design',
                    'Stamps & Seals',
                    'Reflectors',
                    'Mugs',
                    'Bottles',
                    'T-Shirts',
                    'Pens',
                    'Other',
                    'Sub Printing',
                ])
                ->where('order_item_type', 'dtf_print')
                ->count(),
        );

        $this->assertSame(
            0,
            CatalogItem::query()
                ->whereIn('category', [
                    'Receipt Books',
                    'Paper Printing',
                    'Caps',
                    'Design',
                    'Stamps & Seals',
                    'Reflectors',
                    'Mugs',
                    'Bottles',
                    'T-Shirts',
                    'Pens',
                    'Other',
                    'Sub Printing',
                ])
                ->whereNotNull('material_id')
                ->count(),
        );
    }

    public function test_category_correction_updates_catalog_order_items_and_historical_sale_snapshots(): void
    {
        $catalogItem = CatalogItem::create([
            'name' => 'Magic Mug',
            'category' => 'Mugs',
            'order_item_type' => 'dtf_print',
            'unit' => 'piece',
            'default_unit_price' => 800,
            'is_active' => true,
        ]);
        $customer = Customer::create(['name' => 'Existing Mug Customer']);
        $order = Order::create(['customer_id' => $customer->id]);
        $orderItem = $order->items()->create([
            'item_type' => 'dtf_print',
            'catalog_item_id' => $catalogItem->id,
            'catalog_item_name' => $catalogItem->name,
            'catalog_item_unit' => $catalogItem->unit,
            'quantity_or_meters' => 1,
            'unit_price' => 800,
            'subtotal' => 800,
        ]);
        $sale = Sale::create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'total_amount' => 800,
            'amount_paid' => 800,
            'payment_method' => 'cash',
            'completed_at' => now(),
            'items_snapshot' => [[
                'item_type' => 'dtf_print',
                'catalog_item_id' => $catalogItem->id,
                'catalog_item_name' => $catalogItem->name,
                'quantity' => 1,
            ]],
        ]);

        $migration = require database_path('migrations/2026_10_05_073120_correct_catalog_item_categories.php');
        $migration->up();

        $this->assertDatabaseHas('catalog_items', [
            'id' => $catalogItem->id,
            'order_item_type' => 'mugs',
        ]);
        $this->assertDatabaseHas('order_items', [
            'id' => $orderItem->id,
            'item_type' => 'mugs',
        ]);
        $this->assertSame('mugs', $sale->fresh()->items_snapshot[0]['item_type']);
        $this->assertSame('Mugs', $sale->fresh()->items_snapshot[0]['category']);
        $this->get(route('sales.index', ['item_type' => 'mugs']))
            ->assertOk()
            ->assertSee('Existing Mug Customer')
            ->assertSee('Magic Mug');

        $migration->down();

        $this->assertDatabaseHas('catalog_items', [
            'id' => $catalogItem->id,
            'order_item_type' => 'dtf_print',
        ]);
        $this->assertSame('dtf_print', $sale->fresh()->items_snapshot[0]['item_type']);
        $this->assertArrayNotHasKey('category', $sale->fresh()->items_snapshot[0]);
    }

    public function test_expanded_catalog_category_is_saved_to_quick_sale_snapshot_and_can_filter_sales(): void
    {
        $catalogItem = CatalogItem::create([
            'name' => 'Magic Mug',
            'category' => 'Mugs',
            'order_item_type' => 'mugs',
            'unit' => 'piece',
            'default_unit_price' => 800,
            'is_active' => true,
        ]);
        $customer = Customer::create(['name' => 'Mug Customer']);

        $this->post(route('quick-sales.store'), [
            'customer_id' => $customer->id,
            'items' => [[
                'catalog_item_id' => $catalogItem->id,
                'quantity' => 2,
                'unit_price' => 800,
            ]],
            'amount_paid' => 1600,
            'payment_method' => 'cash',
        ])->assertRedirect();

        $sale = Sale::firstOrFail();
        $this->assertSame('mugs', $sale->items_snapshot[0]['item_type']);
        $this->assertSame('Mugs', $sale->items_snapshot[0]['category']);

        $this->get(route('sales.index', ['item_type' => 'mugs']))
            ->assertOk()
            ->assertSee('Mugs')
            ->assertSee('Mug Customer')
            ->assertSee('Magic Mug')
            ->assertSee('value="mugs" selected', false);
    }

    public function test_catalog_form_and_list_accept_and_show_an_expanded_category(): void
    {
        $this->get(route('catalog.create'))
            ->assertOk()
            ->assertSee('value="mugs"', false)
            ->assertSee('Mugs');

        $this->post(route('catalog.store'), [
            'name' => 'Magic Mug',
            'category' => 'Mugs',
            'order_item_type' => 'mugs',
            'unit' => 'piece',
            'default_unit_price' => 800,
        ])->assertRedirect(route('catalog.index'));

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('Magic Mug')
            ->assertSee('Mugs');
    }

    public function test_quick_sale_picker_distinguishes_piece_sticker_prints_from_meter_roll_printing(): void
    {
        $this->seed();

        $this->get(route('quick-sales.create'))
            ->assertSee('Sticker Print A3 · 50.00 / piece')
            ->assertSee('Sticker Print A4 · 30.00 / piece')
            ->assertSee('Sticker Print A5 · 20.00 / piece')
            ->assertSee('White Sticker Printing · 400.00 / meter');
    }
}
