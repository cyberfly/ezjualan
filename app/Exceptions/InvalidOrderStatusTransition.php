<?php

namespace App\Exceptions;

use App\Enums\OrderStatus;
use Exception;

class InvalidOrderStatusTransition extends Exception
{
    public function __construct(OrderStatus $from, OrderStatus $to)
    {
        parent::__construct("Cannot transition order status from [{$from->value}] to [{$to->value}].");
    }
}
