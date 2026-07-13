<?php

use App\Enums\PaymentMethod;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;

test('confirmation page renders order summary for a valid order number', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $order = Order::placeOrder($product, ['name' => 'Ali', 'phone' => '0123456789'], 2, PaymentMethod::Cod, null);

    $response = $this->get(route('orders.confirmation', $order));

    $response->assertOk();
    $response->assertSee($order->order_number);
    $response->assertSee($product->name);
});

test('bank transfer instructions only show for bank transfer orders', function () {
    $product = Product::factory()->create(['stock' => 10]);

    $codOrder = Order::placeOrder($product, ['name' => 'Ali', 'phone' => '0123456789'], 1, PaymentMethod::Cod, null);
    $this->get(route('orders.confirmation', $codOrder))
        ->assertDontSee(config('sistem_jualan.bank_transfer_instructions'));

    $transferOrder = Order::placeOrder($product, ['name' => 'Bakar', 'phone' => '0129876543'], 1, PaymentMethod::BankTransfer, null);
    $this->get(route('orders.confirmation', $transferOrder))
        ->assertSee(config('sistem_jualan.bank_transfer_instructions'));
});

test('unknown order number returns 404', function () {
    $response = $this->get('/pesanan/ORD-NOTFOUND');

    $response->assertNotFound();
});

test('confirmation page shows subtotal and discount rows when a coupon was applied', function () {
    $product = Product::factory()->create(['stock' => 10, 'price' => 100]);
    $coupon = Coupon::factory()->percentage(10)->create();
    $order = Order::placeOrder($product, ['name' => 'Ali', 'phone' => '0123456789'], 1, PaymentMethod::Cod, null, $coupon->code);

    $response = $this->get(route('orders.confirmation', $order));

    $response->assertOk();
    $response->assertSee($coupon->code);
    $response->assertSee('RM10.00');
    $response->assertSee('RM90.00');
});

test('confirmation page omits discount rows when no coupon was applied', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $order = Order::placeOrder($product, ['name' => 'Ali', 'phone' => '0123456789'], 1, PaymentMethod::Cod, null);

    $response = $this->get(route('orders.confirmation', $order));

    $response->assertOk();
    $response->assertDontSee(__('Diskaun'));
});
