<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard shows the correct pending orders count', function () {
    $product = Product::factory()->create(['stock' => 10]);
    Order::placeOrder($product, ['name' => 'Ali', 'phone' => '0111111111'], 1, PaymentMethod::Cod, null);
    Order::placeOrder($product, ['name' => 'Bakar', 'phone' => '0122222222'], 1, PaymentMethod::Cod, null);
    $confirmed = Order::placeOrder($product, ['name' => 'Chong', 'phone' => '0133333333'], 1, PaymentMethod::Cod, null);
    $confirmed->transitionTo(OrderStatus::Confirmed);

    $this->actingAs(User::factory()->create());

    $response = $this->get(route('dashboard'));

    $response->assertSee('2');
});

test('dashboard today revenue excludes pending and cancelled orders', function () {
    $product = Product::factory()->create(['stock' => 10, 'price' => 10]);

    Order::placeOrder($product, ['name' => 'Ali', 'phone' => '0111111111'], 1, PaymentMethod::Cod, null);

    $confirmed = Order::placeOrder($product, ['name' => 'Bakar', 'phone' => '0122222222'], 2, PaymentMethod::Cod, null);
    $confirmed->transitionTo(OrderStatus::Confirmed);

    $cancelled = Order::placeOrder($product, ['name' => 'Chong', 'phone' => '0133333333'], 1, PaymentMethod::Cod, null);
    $cancelled->transitionTo(OrderStatus::Cancelled);

    $this->actingAs(User::factory()->create());

    $response = $this->get(route('dashboard'));

    $response->assertSee('RM20.00');
});

test('dashboard low stock list only includes active products at or under the threshold', function () {
    Product::factory()->create(['name' => 'Stok Cukup', 'stock' => 50]);
    Product::factory()->lowStock()->create(['name' => 'Stok Rendah']);
    Product::factory()->lowStock()->inactive()->create(['name' => 'Tidak Aktif Rendah']);

    $this->actingAs(User::factory()->create());

    $response = $this->get(route('dashboard'));

    $response->assertSee('Stok Rendah');
    $response->assertDontSee('Tidak Aktif Rendah');
});
