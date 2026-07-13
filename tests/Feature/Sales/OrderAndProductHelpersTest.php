<?php

use App\Models\Order;
use App\Models\Product;

test('generateOrderNumber produces the expected format and is unique across calls', function () {
    $numbers = collect(range(1, 5))->map(fn () => Order::generateOrderNumber());

    $numbers->each(function (string $number) {
        expect($number)->toMatch('/^ORD-\d{8}-[A-Z0-9]{4}$/');
    });

    expect($numbers->unique())->toHaveCount(5);
});

test('isLowStock is true at and below the threshold, false above it', function () {
    $threshold = config('sistem_jualan.low_stock_threshold');

    $atThreshold = Product::factory()->create(['stock' => $threshold]);
    $belowThreshold = Product::factory()->create(['stock' => max(0, $threshold - 1)]);
    $aboveThreshold = Product::factory()->create(['stock' => $threshold + 1]);

    expect($atThreshold->isLowStock())->toBeTrue();
    expect($belowThreshold->isLowStock())->toBeTrue();
    expect($aboveThreshold->isLowStock())->toBeFalse();
});
