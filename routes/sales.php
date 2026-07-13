<?php

use App\Http\Controllers\OrderReceiptController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');

    Route::livewire('sales/products', 'pages::sales.products.index')->name('products.index');

    Route::livewire('sales/coupons', 'pages::sales.coupons.index')->name('coupons.index');

    Route::livewire('sales/orders', 'pages::sales.orders.index')->name('orders.index');
    Route::livewire('sales/orders/{order:order_number}', 'pages::sales.orders.show')->name('orders.show');
    Route::get('sales/orders/{order:order_number}/receipt', OrderReceiptController::class)->name('orders.receipt');

    Route::livewire('sales/customers', 'pages::sales.customers.index')->name('customers.index');
    Route::livewire('sales/customers/{customer}', 'pages::sales.customers.show')->name('customers.show');
});
