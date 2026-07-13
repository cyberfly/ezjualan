<?php

use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('staff can list and search customers by name', function () {
    Customer::factory()->create(['name' => 'Ali bin Abu', 'phone' => '0111111111']);
    Customer::factory()->create(['name' => 'Siti binti Ahmad', 'phone' => '0122222222']);

    Livewire::test('pages::sales.customers.index')
        ->set('search', 'Ali')
        ->assertSee('Ali bin Abu')
        ->assertDontSee('Siti binti Ahmad');
});

test('staff can search customers by phone', function () {
    Customer::factory()->create(['name' => 'Ali bin Abu', 'phone' => '0111111111']);
    Customer::factory()->create(['name' => 'Siti binti Ahmad', 'phone' => '0122222222']);

    Livewire::test('pages::sales.customers.index')
        ->set('search', '01222')
        ->assertSee('Siti binti Ahmad')
        ->assertDontSee('Ali bin Abu');
});

test('staff can view a customer order history', function () {
    $customer = Customer::factory()->create(['name' => 'Ali bin Abu']);
    $product = Product::factory()->create(['stock' => 10]);
    $order = Order::placeOrder($product, ['name' => $customer->name, 'phone' => $customer->phone], 1, PaymentMethod::Cod, null);

    $response = $this->get(route('customers.show', $customer));

    $response->assertOk();
    $response->assertSee($order->order_number);
});
