<?php

namespace Database\Factories;

use App\Enums\MaterialType;
use App\Enums\OfferStatus;
use App\Models\Company;
use App\Models\Offer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $provinces = ['Sevilla', 'Córdoba', 'Toledo', 'Valladolid', 'Zaragoza', 'Cuenca'];

        return [
            'company_id' => Company::factory(),
            'material' => $this->faker->randomElement(MaterialType::cases()),
            'quantity_tons' => $this->faker->randomFloat(2, 5, 500),
            'price_per_ton' => $this->faker->randomFloat(2, 300, 600),
            'generation_year' => (int) date('Y'),
            'moisture_percentage' => $this->faker->randomFloat(2, 5, 12),
            'impurities_percentage' => $this->faker->randomFloat(2, 0, 5),
            'province' => $this->faker->randomElement($provinces),
            'municipality' => $this->faker->city(),
            'description' => $this->faker->optional()->sentence(),
            'status' => OfferStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OfferStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OfferStatus::Paused,
            'published_at' => now(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OfferStatus::Closed,
            'published_at' => now(),
            'closed_at' => now(),
        ]);
    }
}
