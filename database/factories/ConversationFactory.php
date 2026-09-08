<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Conversation;
use App\Models\Offer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
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
        ];
    }
}
