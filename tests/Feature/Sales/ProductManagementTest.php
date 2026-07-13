<?php

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('staff can create a product with an auto generated slug', function () {
    Livewire::test('pages::sales.products.form-modal')
        ->call('openModal')
        ->set('name', 'Kopi O Ais')
        ->set('price', '4.50')
        ->set('stock', 20)
        ->call('save');

    $product = Product::where('name', 'Kopi O Ais')->first();

    expect($product)->not->toBeNull();
    expect($product->slug)->toBe('kopi-o-ais');
});

test('duplicate product names get a unique slug', function () {
    Product::factory()->create(['name' => 'Kopi O Ais']);

    Livewire::test('pages::sales.products.form-modal')
        ->call('openModal')
        ->set('name', 'Kopi O Ais')
        ->set('price', '4.50')
        ->set('stock', 20)
        ->call('save');

    expect(Product::where('slug', 'kopi-o-ais-2')->exists())->toBeTrue();
});

test('staff can edit an existing product', function () {
    $product = Product::factory()->create(['name' => 'Nama Lama', 'price' => 5]);

    Livewire::test('pages::sales.products.form-modal')
        ->call('openModal', $product->id)
        ->set('name', 'Nama Baharu')
        ->set('price', '9.90')
        ->set('stock', $product->stock)
        ->call('save');

    expect($product->refresh()->name)->toBe('Nama Baharu');
    expect((float) $product->price)->toBe(9.9);
});

test('staff can soft delete a product and it disappears from the catalog and staff list', function () {
    $product = Product::factory()->create(['name' => 'Produk Untuk Dipadam']);

    Livewire::test('pages::sales.products.index')
        ->call('deleteProduct', $product->id);

    expect($product->refresh()->trashed())->toBeTrue();

    $this->get(route('home'))->assertDontSee('Produk Untuk Dipadam');
    $this->get(route('products.index'))->assertDontSee('Produk Untuk Dipadam');
});

test('deleted products still display correctly on past orders via snapshot fields', function () {
    $product = Product::factory()->create(['name' => 'Produk Lama', 'price' => 7.5, 'stock' => 10]);
    $order = Order::placeOrder($product, ['name' => 'Ali', 'phone' => '0123456789'], 1, PaymentMethod::Cod, null);
    $product->delete();

    $response = $this->get(route('orders.show', $order));

    $response->assertOk();
    $response->assertSee('Produk Lama');
});

test('staff product search filters the list by name', function () {
    Product::factory()->create(['name' => 'Kopi O Ais']);
    Product::factory()->create(['name' => 'Teh Tarik']);

    Livewire::test('pages::sales.products.index')
        ->set('search', 'Kopi')
        ->assertSee('Kopi O Ais')
        ->assertDontSee('Teh Tarik');
});
