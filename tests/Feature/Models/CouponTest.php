<?php

use App\Models\Coupon;

test('percentage discount is clamped to 100 percent', function () {
    $coupon = Coupon::factory()->percentage(150)->make();

    expect($coupon->calculateDiscount(200.0))->toBe(200.0);
});

test('fixed discount is clamped to the subtotal', function () {
    $coupon = Coupon::factory()->fixed(50)->make();

    expect($coupon->calculateDiscount(30.0))->toBe(30.0);
});

test('percentage discount is calculated correctly', function () {
    $coupon = Coupon::factory()->percentage(10)->make();

    expect($coupon->calculateDiscount(200.0))->toBe(20.0);
});

test('a plain coupon is valid', function () {
    $coupon = Coupon::factory()->make();

    expect($coupon->isValidFor())->toBeTrue();
});

test('an inactive coupon is not valid', function () {
    $coupon = Coupon::factory()->inactive()->make();

    expect($coupon->isValidFor())->toBeFalse();
});

test('an expired coupon is not valid', function () {
    $coupon = Coupon::factory()->expired()->make();

    expect($coupon->isValidFor())->toBeFalse();
    expect($coupon->isExpired())->toBeTrue();
});

test('a coupon that has not started yet is not valid', function () {
    $coupon = Coupon::factory()->notYetStarted()->make();

    expect($coupon->isValidFor())->toBeFalse();
    expect($coupon->isExpired())->toBeTrue();
});

test('an exhausted coupon is not valid', function () {
    $coupon = Coupon::factory()->exhausted()->make();

    expect($coupon->isValidFor())->toBeFalse();
    expect($coupon->isExhausted())->toBeTrue();
});
