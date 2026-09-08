<?php

namespace Tests\Feature\Offers;

use App\Enums\MaterialType;
use App\Enums\OfferStatus;
use App\Models\Company;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_offer_with_an_exact_location_computes_a_public_one(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $company->users()->attach($user, ['is_primary' => true]);

        $this->actingAs($user)->post('/ofertas', [
            'material' => MaterialType::Cardboard->value,
            'quantity_tons' => 10,
            'price_per_ton' => 300,
            'generation_year' => (int) date('Y'),
            'province' => 'Sevilla',
            'exact_latitude' => 37.389,
            'exact_longitude' => -5.984,
        ]);

        $offer = Offer::firstOrFail();

        $this->assertNotNull($offer->exact_latitude);
        $this->assertNotNull($offer->public_latitude);
        $this->assertNotNull($offer->public_longitude);
        $this->assertNotEquals((float) $offer->exact_latitude, (float) $offer->public_latitude);
    }

    public function test_the_public_location_is_stable_across_updates_that_keep_the_same_exact_location(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $company->users()->attach($user, ['is_primary' => true]);
        $offer = Offer::factory()->for($company)->create([
            'exact_latitude' => 37.389,
            'exact_longitude' => -5.984,
        ]);
        $offer->refreshPublicLocation();
        $offer->save();
        $firstPublicLatitude = (float) $offer->public_latitude;
        $firstPublicLongitude = (float) $offer->public_longitude;

        $this->actingAs($user)->put("/ofertas/{$offer->id}", [
            'material' => $offer->material->value,
            'quantity_tons' => 20,
            'price_per_ton' => (float) $offer->price_per_ton,
            'generation_year' => $offer->generation_year,
            'province' => $offer->province,
            'exact_latitude' => 37.389,
            'exact_longitude' => -5.984,
        ]);

        $offer->refresh();

        $this->assertSame($firstPublicLatitude, (float) $offer->public_latitude);
        $this->assertSame($firstPublicLongitude, (float) $offer->public_longitude);
    }

    public function test_public_location_cannot_be_mass_assigned_directly(): void
    {
        $offer = new Offer([
            'public_latitude' => 40.0,
            'public_longitude' => -3.0,
        ]);

        $this->assertNull($offer->public_latitude);
        $this->assertNull($offer->public_longitude);
    }

    public function test_the_marketplace_never_exposes_exact_coordinates(): void
    {
        $buyer = $this->approvedUserWithCompany();
        $sellerCompany = Company::factory()->create();
        Offer::factory()->for($sellerCompany)->create([
            'status' => OfferStatus::Published,
            'published_at' => now(),
            'exact_latitude' => 37.389,
            'exact_longitude' => -5.984,
        ]);

        $indexResponse = $this->actingAs($buyer)->get('/mercado');
        $indexResponse->assertInertia(fn ($page) => $page
            ->has('offers.data.0')
            ->missing('offers.data.0.exact_latitude')
            ->missing('offers.data.0.exact_longitude')
            ->has('offers.data.0.public_latitude'));

        $offer = Offer::firstOrFail();
        $showResponse = $this->actingAs($buyer)->get("/mercado/{$offer->id}");
        $showResponse->assertInertia(fn ($page) => $page
            ->missing('offer.exact_latitude')
            ->missing('offer.exact_longitude'));
    }

    public function test_only_the_owning_company_can_see_the_exact_location(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create();
        $company->users()->attach($owner, ['is_primary' => true]);
        $offer = Offer::factory()->for($company)->create([
            'exact_latitude' => 37.389,
            'exact_longitude' => -5.984,
        ]);

        $outsider = $this->approvedUserWithCompany();

        $this->assertTrue($owner->can('viewExactLocation', $offer));
        $this->assertFalse($outsider->can('viewExactLocation', $offer));
    }

    private function approvedUserWithCompany(): User
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $company->users()->attach($user, ['is_primary' => true]);

        return $user;
    }
}
