<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\InvalidOrderStatusTransition;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

function placeTestOrder(?OrderStatus $status = null): Order
{
    $product = Product::factory()->create(['stock' => 10]);
    $order = Order::placeOrder($product, ['name' => 'Ali', 'phone' => '01'.random_int(10000000, 99999999)], 1, PaymentMethod::Cod, null);

    if ($status) {
        // Directly set the arrangement status rather than routing through transitionTo(),
        // since some target statuses (e.g. Shipped) aren't reachable in one hop from Pending.
        $order->update(['status' => $status]);
    }

    return $order;
}

test('staff can list orders and filter by status', function () {
    $pending = placeTestOrder();
    $confirmed = placeTestOrder(OrderStatus::Confirmed);

    Livewire::test('pages::sales.orders.index')
        ->set('status', OrderStatus::Confirmed->value)
        ->assertSee($confirmed->order_number)
        ->assertDontSee($pending->order_number);
});

test('staff can view a single order', function () {
    $order = placeTestOrder();

    $response = $this->get(route('orders.show', $order));

    $response->assertOk();
    $response->assertSee($order->order_number);
    $response->assertSee($order->customer->name);
});

test('valid status transitions succeed in sequence', function () {
    $order = placeTestOrder();

    $order->transitionTo(OrderStatus::Confirmed);
    expect($order->status)->toBe(OrderStatus::Confirmed);

    $order->transitionTo(OrderStatus::Shipped);
    expect($order->status)->toBe(OrderStatus::Shipped);

    $order->transitionTo(OrderStatus::Completed);
    expect($order->status)->toBe(OrderStatus::Completed);
});

test('invalid status transitions are rejected', function (OrderStatus $from, OrderStatus $to) {
    $order = placeTestOrder($from);

    expect(fn () => $order->transitionTo($to))->toThrow(InvalidOrderStatusTransition::class);
})->with([
    [OrderStatus::Pending, OrderStatus::Shipped],
    [OrderStatus::Pending, OrderStatus::Completed],
    [OrderStatus::Confirmed, OrderStatus::Completed],
    [OrderStatus::Confirmed, OrderStatus::Pending],
    [OrderStatus::Shipped, OrderStatus::Pending],
    [OrderStatus::Shipped, OrderStatus::Confirmed],
    [OrderStatus::Shipped, OrderStatus::Cancelled],
    [OrderStatus::Completed, OrderStatus::Pending],
    [OrderStatus::Completed, OrderStatus::Cancelled],
    [OrderStatus::Cancelled, OrderStatus::Pending],
]);

test('cancelling from pending restocks the product', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $order = Order::placeOrder($product, ['name' => 'Ali', 'phone' => '0123456789'], 3, PaymentMethod::Cod, null);

    expect($product->refresh()->stock)->toBe(7);

    $order->transitionTo(OrderStatus::Cancelled);

    expect($product->refresh()->stock)->toBe(10);
});

test('cancelling from confirmed restocks the product', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $order = Order::placeOrder($product, ['name' => 'Ali', 'phone' => '0123456789'], 3, PaymentMethod::Cod, null);
    $order->transitionTo(OrderStatus::Confirmed);

    $order->transitionTo(OrderStatus::Cancelled);

    expect($product->refresh()->stock)->toBe(10);
});

test('cancelling from shipped or completed is rejected', function (OrderStatus $status) {
    $order = placeTestOrder($status);

    expect(fn () => $order->transitionTo(OrderStatus::Cancelled))->toThrow(InvalidOrderStatusTransition::class);
})->with([
    [OrderStatus::Shipped],
    [OrderStatus::Completed],
]);
