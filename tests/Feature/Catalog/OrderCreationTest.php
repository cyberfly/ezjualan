<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Livewire\Livewire;

test('guest can view the order form for an active in-stock product', function () {
    $product = Product::factory()->create(['stock' => 10]);

    $response = $this->get(route('orders.create', $product));

    $response->assertOk();
    $response->assertSee($product->name);
});

test('ordering an inactive product returns 404', function () {
    $product = Product::factory()->inactive()->create();

    $response = $this->get(route('orders.create', $product));

    $response->assertNotFound();
});

test('ordering an out of stock product returns 404', function () {
    $product = Product::factory()->outOfStock()->create();

    $response = $this->get(route('orders.create', $product));

    $response->assertNotFound();
});

test('guest can submit a valid order', function () {
    $product = Product::factory()->create(['stock' => 10, 'price' => 12.5]);

    Livewire::test('pages::catalog.order', ['product' => $product])
        ->set('quantity', 3)
        ->set('name', 'Ali bin Abu')
        ->set('phone', '0123456789')
        ->set('paymentMethod', PaymentMethod::Cod->value)
        ->call('submit')
        ->assertRedirect();

    expect(Customer::where('phone', '0123456789')->exists())->toBeTrue();

    $order = Order::first();
    expect($order->quantity)->toBe(3);
    expect((float) $order->total_price)->toBe(37.5);
    expect($order->status)->toBe(OrderStatus::Pending);

    expect($product->refresh()->stock)->toBe(7);
});

test('ordering more than available stock is rejected without side effects', function () {
    $product = Product::factory()->create(['stock' => 2]);

    Livewire::test('pages::catalog.order', ['product' => $product])
        ->set('quantity', 5)
        ->set('name', 'Ali bin Abu')
        ->set('phone', '0123456789')
        ->set('paymentMethod', PaymentMethod::Cod->value)
        ->call('submit')
        ->assertHasErrors('quantity');

    expect(Order::count())->toBe(0);
    expect($product->refresh()->stock)->toBe(2);
});

test('repeat phone number updates existing customer instead of duplicating', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $existing = Customer::factory()->create(['phone' => '0123456789', 'name' => 'Nama Lama']);

    Livewire::test('pages::catalog.order', ['product' => $product])
        ->set('quantity', 1)
        ->set('name', 'Nama Baharu')
        ->set('phone', '0123456789')
        ->set('paymentMethod', PaymentMethod::Cod->value)
        ->call('submit')
        ->assertRedirect();

    expect(Customer::count())->toBe(1);
    expect($existing->refresh()->name)->toBe('Nama Baharu');
});
