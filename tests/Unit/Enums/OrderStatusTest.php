<?php

use App\Enums\OrderStatus;

test('transition matrix allows only the defined lifecycle moves', function (OrderStatus $from, OrderStatus $to, bool $expected) {
    expect($from->canTransitionTo($to))->toBe($expected);
})->with(function () {
    $cases = OrderStatus::cases();
    $allowed = [
        OrderStatus::Pending->value.'->'.OrderStatus::Confirmed->value,
        OrderStatus::Pending->value.'->'.OrderStatus::Cancelled->value,
        OrderStatus::Confirmed->value.'->'.OrderStatus::Shipped->value,
        OrderStatus::Confirmed->value.'->'.OrderStatus::Cancelled->value,
        OrderStatus::Shipped->value.'->'.OrderStatus::Completed->value,
    ];

    foreach ($cases as $from) {
        foreach ($cases as $to) {
            if ($from === $to) {
                continue;
            }

            $key = $from->value.'->'.$to->value;

            yield "{$from->value} -> {$to->value}" => [$from, $to, in_array($key, $allowed, true)];
        }
    }
});

test('terminal statuses have no allowed next statuses', function () {
    expect(OrderStatus::Completed->allowedNextStatuses())->toBe([]);
    expect(OrderStatus::Cancelled->allowedNextStatuses())->toBe([]);
});
