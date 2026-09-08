<?php

namespace Database\Factories;

use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
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
            'trade_name' => $this->faker->company(),
            'legal_name' => $this->faker->company().' S.L.',
            'tax_id' => 'B'.$this->faker->unique()->numerify('########'),
            'company_type' => $this->faker->randomElement(CompanyType::cases()),
            'address' => $this->faker->streetAddress(),
            'postal_code' => $this->faker->numerify('#####'),
            'municipality' => $this->faker->city(),
            'province' => $this->faker->randomElement($provinces),
            'autonomous_community' => $this->faker->randomElement(['Andalucía', 'Castilla-La Mancha', 'Castilla y León', 'Aragón']),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->companyEmail(),
            'status' => CompanyStatus::Approved,
            'approved_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CompanyStatus::Pending,
            'approved_at' => null,
        ]);
    }
}
