<?php

namespace Tests\Feature\Market;

use App\Enums\MaterialType;
use App\Models\Company;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    protected User $buyer;

    protected function setUp(): void
    {
        parent::setUp();

        $buyerCompany = Company::factory()->create();
        $this->buyer = User::factory()->create();
        $buyerCompany->users()->attach($this->buyer, ['is_primary' => true]);
    }

    public function test_only_published_offers_appear_in_the_market_listing(): void
    {
        $seller = Company::factory()->create();
        $published = Offer::factory()->for($seller)->published()->create();
        Offer::factory()->for($seller)->create();
        Offer::factory()->for($seller)->paused()->create();
        Offer::factory()->for($seller)->closed()->create();

        $response = $this->actingAs($this->buyer)->get('/mercado');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('market/Index')
            ->has('offers.data', 1)
            ->where('offers.data.0.id', $published->id)
        );
    }

    public function test_filtering_by_crop(): void
    {
        $seller = Company::factory()->create();
        $cardboard = Offer::factory()->for($seller)->published()->create(['material' => MaterialType::Cardboard]);
        Offer::factory()->for($seller)->published()->create(['material' => MaterialType::Plastic]);

        $response = $this->actingAs($this->buyer)->get('/mercado?material=carton');

        $response->assertInertia(fn ($page) => $page
            ->has('offers.data', 1)
            ->where('offers.data.0.id', $cardboard->id)
        );
    }

    public function test_filtering_by_province(): void
    {
        $seller = Company::factory()->create();
        $sevilla = Offer::factory()->for($seller)->published()->create(['province' => 'Sevilla']);
        Offer::factory()->for($seller)->published()->create(['province' => 'Cuenca']);

        $response = $this->actingAs($this->buyer)->get('/mercado?province=Sevilla');

        $response->assertInertia(fn ($page) => $page
            ->has('offers.data', 1)
            ->where('offers.data.0.id', $sevilla->id)
        );
    }

    public function test_filtering_by_price_and_quantity_range(): void
    {
        $seller = Company::factory()->create();
        $cheap = Offer::factory()->for($seller)->published()->create(['price_per_ton' => 300, 'quantity_tons' => 10]);
        $expensive = Offer::factory()->for($seller)->published()->create(['price_per_ton' => 550, 'quantity_tons' => 200]);

        $this->actingAs($this->buyer)
            ->get('/mercado?max_price=400')
            ->assertInertia(fn ($page) => $page->has('offers.data', 1)->where('offers.data.0.id', $cheap->id));

        $this->actingAs($this->buyer)
            ->get('/mercado?min_quantity=100')
            ->assertInertia(fn ($page) => $page->has('offers.data', 1)->where('offers.data.0.id', $expensive->id));
    }

    public function test_a_published_offers_detail_page_shows_company_contact_info(): void
    {
        $seller = Company::factory()->create(['phone' => '600111222', 'email' => 'ventas@empresa.test']);
        $offer = Offer::factory()->for($seller)->published()->create();

        $response = $this->actingAs($this->buyer)->get("/mercado/{$offer->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('market/Show')
            ->where('offer.id', $offer->id)
            ->where('offer.company.phone', '600111222')
            ->where('offer.company.email', 'ventas@empresa.test')
        );
    }

    public function test_a_draft_offer_is_not_visible_in_the_market(): void
    {
        $seller = Company::factory()->create();
        $draft = Offer::factory()->for($seller)->create();

        $this->actingAs($this->buyer)->get("/mercado/{$draft->id}")->assertNotFound();
    }

    public function test_a_closed_offer_is_not_visible_in_the_market(): void
    {
        $seller = Company::factory()->create();
        $closed = Offer::factory()->for($seller)->closed()->create();

        $this->actingAs($this->buyer)->get("/mercado/{$closed->id}")->assertNotFound();
    }
}
