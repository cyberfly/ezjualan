<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Coupon;
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

test('order without a coupon code has zero discount', function () {
    $product = Product::factory()->create(['stock' => 10, 'price' => 10]);

    Livewire::test('pages::catalog.order', ['product' => $product])
        ->set('quantity', 2)
        ->set('name', 'Ali bin Abu')
        ->set('phone', '0123456789')
        ->set('paymentMethod', PaymentMethod::Cod->value)
        ->call('submit')
        ->assertRedirect();

    $order = Order::first();
    expect((float) $order->subtotal)->toBe(20.0);
    expect((float) $order->discount_amount)->toBe(0.0);
    expect((float) $order->total_price)->toBe(20.0);
});

test('guest can apply a valid percentage coupon and total reflects the discount', function () {
    $product = Product::factory()->create(['stock' => 10, 'price' => 100]);
    $coupon = Coupon::factory()->percentage(10)->create();

    Livewire::test('pages::catalog.order', ['product' => $product])
        ->set('quantity', 1)
        ->set('couponCode', $coupon->code)
        ->call('applyCoupon')
        ->assertHasNoErrors()
        ->set('name', 'Ali bin Abu')
        ->set('phone', '0123456789')
        ->set('paymentMethod', PaymentMethod::Cod->value)
        ->call('submit')
        ->assertRedirect();

    $order = Order::first();
    expect((float) $order->subtotal)->toBe(100.0);
    expect((float) $order->discount_amount)->toBe(10.0);
    expect((float) $order->total_price)->toBe(90.0);
    expect($order->coupon_code)->toBe($coupon->code);
    expect($coupon->refresh()->used_count)->toBe(1);
});

test('guest can apply a valid fixed coupon and total reflects the discount', function () {
    $product = Product::factory()->create(['stock' => 10, 'price' => 50]);
    $coupon = Coupon::factory()->fixed(5)->create();

    Livewire::test('pages::catalog.order', ['product' => $product])
        ->set('quantity', 1)
        ->set('couponCode', $coupon->code)
        ->call('applyCoupon')
        ->assertHasNoErrors()
        ->set('name', 'Ali bin Abu')
        ->set('phone', '0123456789')
        ->set('paymentMethod', PaymentMethod::Cod->value)
        ->call('submit')
        ->assertRedirect();

    $order = Order::first();
    expect((float) $order->discount_amount)->toBe(5.0);
    expect((float) $order->total_price)->toBe(45.0);
    expect($coupon->refresh()->used_count)->toBe(1);
});

test('fixed coupon discount is clamped to the subtotal', function () {
    $product = Product::factory()->create(['stock' => 10, 'price' => 10]);
    $coupon = Coupon::factory()->fixed(50)->create();

    Livewire::test('pages::catalog.order', ['product' => $product])
        ->set('quantity', 1)
        ->set('couponCode', $coupon->code)
        ->set('name', 'Ali bin Abu')
        ->set('phone', '0123456789')
        ->set('paymentMethod', PaymentMethod::Cod->value)
        ->call('submit')
        ->assertRedirect();

    $order = Order::first();
    expect((float) $order->discount_amount)->toBe(10.0);
    expect((float) $order->total_price)->toBe(0.0);
});

test('expired coupon is rejected at checkout', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $coupon = Coupon::factory()->expired()->create();

    Livewire::test('pages::catalog.order', ['product' => $product])
        ->set('quantity', 1)
        ->set('couponCode', $coupon->code)
        ->set('name', 'Ali bin Abu')
        ->set('phone', '0123456789')
        ->set('paymentMethod', PaymentMethod::Cod->value)
        ->call('submit')
        ->assertHasErrors('couponCode');

    expect(Order::count())->toBe(0);
    expect($product->refresh()->stock)->toBe(10);
});

test('exhausted coupon is rejected at checkout', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $coupon = Coupon::factory()->exhausted()->create();

    Livewire::test('pages::catalog.order', ['product' => $product])
        ->set('quantity', 1)
        ->set('couponCode', $coupon->code)
        ->set('name', 'Ali bin Abu')
        ->set('phone', '0123456789')
        ->set('paymentMethod', PaymentMethod::Cod->value)
        ->call('submit')
        ->assertHasErrors('couponCode');

    expect(Order::count())->toBe(0);
});

test('inactive coupon is rejected at checkout', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $coupon = Coupon::factory()->inactive()->create();

    Livewire::test('pages::catalog.order', ['product' => $product])
        ->set('quantity', 1)
        ->set('couponCode', $coupon->code)
        ->set('name', 'Ali bin Abu')
        ->set('phone', '0123456789')
        ->set('paymentMethod', PaymentMethod::Cod->value)
        ->call('submit')
        ->assertHasErrors('couponCode');

    expect(Order::count())->toBe(0);
});

test('unknown coupon code is rejected at checkout', function () {
    $product = Product::factory()->create(['stock' => 10]);

    Livewire::test('pages::catalog.order', ['product' => $product])
        ->set('quantity', 1)
        ->set('couponCode', 'TIDAK-WUJUD')
        ->set('name', 'Ali bin Abu')
        ->set('phone', '0123456789')
        ->set('paymentMethod', PaymentMethod::Cod->value)
        ->call('submit')
        ->assertHasErrors('couponCode');

    expect(Order::count())->toBe(0);
});
