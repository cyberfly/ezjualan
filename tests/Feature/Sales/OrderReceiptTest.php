<?php

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

test('authenticated staff can view the printable receipt', function () {
    $this->actingAs(User::factory()->create());

    $product = Product::factory()->create(['stock' => 10]);
    $order = Order::placeOrder($product, ['name' => 'Ali', 'phone' => '0123456789'], 1, PaymentMethod::Cod, null);

    $response = $this->get(route('orders.receipt', $order));

    $response->assertOk();
    $response->assertSee($order->order_number);
});

test('guest is redirected to login when viewing a receipt', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $order = Order::placeOrder($product, ['name' => 'Ali', 'phone' => '0123456789'], 1, PaymentMethod::Cod, null);

    $response = $this->get(route('orders.receipt', $order));

    $response->assertRedirect(route('login'));
});
