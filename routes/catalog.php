<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::catalog.index')->name('home');

Route::livewire('/pesan/{product:slug}', 'pages::catalog.order')->name('orders.create');

Route::livewire('/pesanan/{order:order_number}', 'pages::catalog.confirmation')->name('orders.confirmation');
