<?php

use App\Models\Product;

test('guest can view the storefront', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('storefront shows only active products', function () {
    $active = Product::factory()->create(['name' => 'Produk Aktif']);
    $inactive = Product::factory()->inactive()->create(['name' => 'Produk Tidak Aktif']);
    $deleted = Product::factory()->create(['name' => 'Produk Dipadam']);
    $deleted->delete();

    $response = $this->get(route('home'));

    $response->assertSee('Produk Aktif');
    $response->assertDontSee('Produk Tidak Aktif');
    $response->assertDontSee('Produk Dipadam');
});

test('storefront shows product name, price and stock badges', function () {
    Product::factory()->create(['name' => 'Kopi O', 'price' => 5.5, 'stock' => 20]);

    $response = $this->get(route('home'));

    $response->assertSee('Kopi O');
    $response->assertSee('RM5.50');
});
