<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Contracts\View\View;

class OrderReceiptController extends Controller
{
    public function __invoke(Order $order): View
    {
        return view('pages.sales.orders.receipt', ['order' => $order]);
    }
}
