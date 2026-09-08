<?php

namespace Tests\Feature\Offers;

use App\Enums\CompanyStatus;
use App\Enums\OfferStatus;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Offer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferTradeabilityTest extends TestCase
{
    use RefreshDatabase;

    protected Company $buyerCompany;

    protected User $buyer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buyerCompany = Company::factory()->create();
        $this->buyer = User::factory()->create();
        $this->buyerCompany->users()->attach($this->buyer, ['is_primary' => true]);
    }

    public function test_a_blocked_companys_offer_is_not_tradeable(): void
    {
        $seller = Company::factory()->create();
        $offer = Offer::factory()->for($seller)->published()->create();
        $this->assertTrue($offer->isTradeable());

        $seller->status = CompanyStatus::Blocked;
        $seller->save();
        $offer->refresh();

        $this->assertFalse($offer->isTradeable());
    }

    public function test_a_blocked_companys_offer_does_not_appear_in_the_market_listing(): void
    {
        $seller = Company::factory()->create(['status' => CompanyStatus::Blocked]);
        Offer::factory()->for($seller)->published()->create();

        $response = $this->actingAs($this->buyer)->get('/mercado');

        $response->assertInertia(fn ($page) => $page->has('offers.data', 0));
    }

    public function test_visiting_a_blocked_companys_offer_page_returns_404(): void
    {
        $seller = Company::factory()->create(['status' => CompanyStatus::Blocked]);
        $offer = Offer::factory()->for($seller)->published()->create();

        $this->actingAs($this->buyer)->get("/mercado/{$offer->id}")->assertNotFound();
    }

    public function test_cannot_contact_a_blocked_companys_offer(): void
    {
        $seller = Company::factory()->create(['status' => CompanyStatus::Blocked]);
        $offer = Offer::factory()->for($seller)->published()->create();

        $this->actingAs($this->buyer)
            ->post("/mercado/{$offer->id}/contactar")
            ->assertForbidden();

        $this->assertSame(0, Conversation::count());
    }

    public function test_cannot_order_from_a_blocked_companys_offer(): void
    {
        $seller = Company::factory()->create(['status' => CompanyStatus::Blocked]);
        $offer = Offer::factory()->for($seller)->published()->create();

        $this->actingAs($this->buyer)
            ->post("/mercado/{$offer->id}/pedidos", ['quantity_tons' => 5])
            ->assertForbidden();

        $this->assertSame(0, Order::count());
    }

    public function test_cannot_contact_about_a_non_published_offer(): void
    {
        $seller = Company::factory()->create();
        $draft = Offer::factory()->for($seller)->create();

        $this->assertFalse($this->buyer->can('startFor', [Conversation::class, $draft]));
    }

    public function test_cannot_place_an_order_on_a_non_published_offer(): void
    {
        $seller = Company::factory()->create();
        $paused = Offer::factory()->for($seller)->create();
        $paused->status = OfferStatus::Paused;
        $paused->save();

        $this->assertFalse($this->buyer->can('placeFor', [Order::class, $paused]));
    }
}
