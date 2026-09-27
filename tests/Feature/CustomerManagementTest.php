<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderArtwork;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_customers_can_be_created_and_searched_by_phone(): void
    {
        $response = $this->post(route('customers.store'), [
            'name' => 'Jane Sample',
            'phone' => '0712345678',
            'is_walk_in' => true,
        ]);
        $customer = Customer::where('name', 'Jane Sample')->firstOrFail();
        $response->assertRedirect(route('customers.show', $customer));

        $response = $this->get(route('customers.index', ['q' => '0712345678']));

        $response->assertOk()->assertSee($customer->name)->assertSee('Walk-in');
    }

    public function test_customer_detail_shows_aggregated_order_quantities_and_artworks(): void
    {
        $customer = Customer::create(['name' => 'History Customer']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'completed',
            'total_amount' => 50,
        ]);
        $banner = OrderItem::create([
            'order_id' => $order->id,
            'item_type' => 'banner',
            'quantity_or_meters' => 2.5,
            'unit_price' => 10,
            'subtotal' => 25,
        ]);
        $garment = OrderItem::create([
            'order_id' => $order->id,
            'item_type' => 'dtf_garment',
            'quantity_or_meters' => 3,
            'unit_price' => 10,
            'subtotal' => 30,
            'garment_sourced_by_shop' => false,
        ]);
        $artwork = OrderArtwork::create([
            'order_item_id' => $garment->id,
            'purpose' => 'design',
            'file_path' => 'artworks/design.pdf',
            'file_type' => 'pdf',
        ]);

        $response = $this->get(route('customers.show', $customer));

        $response->assertOk()
            ->assertSee('2.500')
            ->assertSee('>3 <', false)
            ->assertSee('pieces')
            ->assertSee('Order #'.$order->id)
            ->assertSee('Designs &amp; production proofs', false)
            ->assertSee(route('artworks.download', $artwork), false);
    }

    public function test_customer_information_can_be_updated(): void
    {
        $customer = Customer::create(['name' => 'Original Name']);

        $response = $this->put(route('customers.update', $customer), [
            'name' => 'Updated Name',
            'phone' => '0700000000',
            'is_walk_in' => '1',
        ]);

        $response->assertRedirect(route('customers.show', $customer));
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Name',
            'phone' => '0700000000',
            'is_walk_in' => true,
        ]);
    }
}
