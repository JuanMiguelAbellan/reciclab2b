<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Company;
use App\Models\Offer;
use App\Models\Order;
use App\Models\User;
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
        return [
            'offer_id' => Offer::factory(),
            'buyer_company_id' => Company::factory(),
            'created_by' => User::factory(),
            'quantity_tons' => $this->faker->randomFloat(2, 1, 50),
            'price_per_ton' => $this->faker->randomFloat(2, 300, 600),
            'status' => OrderStatus::Pending,
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Accepted,
            'responded_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Rejected,
            'responded_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Completed,
            'responded_at' => now(),
            'completed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
