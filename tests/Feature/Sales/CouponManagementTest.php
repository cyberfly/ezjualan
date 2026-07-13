<?php

use App\Enums\CouponType;
use App\Enums\PaymentMethod;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('staff can create a percentage coupon', function () {
    Livewire::test('pages::sales.coupons.form-modal')
        ->call('openModal')
        ->set('code', 'diskaun10')
        ->set('type', CouponType::Percentage->value)
        ->set('value', '10')
        ->call('save');

    $coupon = Coupon::where('code', 'DISKAUN10')->first();

    expect($coupon)->not->toBeNull();
    expect($coupon->type)->toBe(CouponType::Percentage);
    expect((float) $coupon->value)->toBe(10.0);
});

test('staff can create a fixed coupon', function () {
    Livewire::test('pages::sales.coupons.form-modal')
        ->call('openModal')
        ->set('code', 'RM10OFF')
        ->set('type', CouponType::Fixed->value)
        ->set('value', '10')
        ->call('save');

    $coupon = Coupon::where('code', 'RM10OFF')->first();

    expect($coupon)->not->toBeNull();
    expect($coupon->type)->toBe(CouponType::Fixed);
});

test('staff can edit an existing coupon', function () {
    $coupon = Coupon::factory()->percentage(10)->create(['code' => 'LAMA']);

    Livewire::test('pages::sales.coupons.form-modal')
        ->call('openModal', $coupon->id)
        ->set('code', 'BAHARU')
        ->set('value', '20')
        ->call('save');

    expect($coupon->refresh()->code)->toBe('BAHARU');
    expect((float) $coupon->value)->toBe(20.0);
});

test('staff can delete a coupon', function () {
    $coupon = Coupon::factory()->create();

    Livewire::test('pages::sales.coupons.index')
        ->call('deleteCoupon', $coupon->id);

    expect(Coupon::find($coupon->id))->toBeNull();
});

test('past orders still display the coupon code snapshot after the coupon is deleted', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $coupon = Coupon::factory()->percentage(10)->create(['code' => 'LAMAKUPON']);
    $order = Order::placeOrder($product, ['name' => 'Ali', 'phone' => '0123456789'], 1, PaymentMethod::Cod, null, $coupon->code);

    $coupon->delete();

    $response = $this->get(route('orders.show', $order));

    $response->assertOk();
    $response->assertSee('LAMAKUPON');
});

test('duplicate coupon codes are rejected', function () {
    Coupon::factory()->create(['code' => 'SEDIAADA']);

    Livewire::test('pages::sales.coupons.form-modal')
        ->call('openModal')
        ->set('code', 'SEDIAADA')
        ->set('type', CouponType::Percentage->value)
        ->set('value', '10')
        ->call('save')
        ->assertHasErrors('code');
});

test('expires_at before starts_at is rejected', function () {
    Livewire::test('pages::sales.coupons.form-modal')
        ->call('openModal')
        ->set('code', 'TARIKH')
        ->set('type', CouponType::Percentage->value)
        ->set('value', '10')
        ->set('startsAt', now()->addDays(5)->format('Y-m-d\TH:i'))
        ->set('expiresAt', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors('expiresAt');
});

test('staff coupon search filters the list by code', function () {
    Coupon::factory()->create(['code' => 'DISKAUN10']);
    Coupon::factory()->create(['code' => 'PROMOSPESIAL']);

    Livewire::test('pages::sales.coupons.index')
        ->set('search', 'DISKAUN')
        ->assertSee('DISKAUN10')
        ->assertDontSee('PROMOSPESIAL');
});
