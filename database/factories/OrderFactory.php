<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = fake()->randomFloat(2, 5, 500);

        return [
            'order_number' => Order::generateOrderNumber(),
            'customer_id' => Customer::factory(),
            'product_id' => Product::factory(),
            'product_name' => fake()->word().' '.fake()->word().' '.fake()->word(),
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'total_price' => $unitPrice * $quantity,
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'status' => OrderStatus::Pending,
            'notes' => null,
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
