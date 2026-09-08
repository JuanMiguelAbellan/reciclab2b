<?php

namespace Tests\Feature\Offers;

use App\Actions\Conversations\SendMessageAction;
use App\Actions\Offers\CloseOfferAction;
use App\Actions\Offers\PauseOfferAction;
use App\Actions\Offers\PublishOfferAction;
use App\Actions\Offers\UpdateOfferAction;
use App\Actions\Orders\RejectOrderAction;
use App\Enums\MaterialType;
use App\Enums\OfferStatus;
use App\Enums\OrderStatus;
use App\Models\Company;
use App\Models\Offer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class OfferLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $member;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->member = User::factory()->create();
        $this->company->users()->attach($this->member, ['is_primary' => true]);
    }

    public function test_the_index_create_and_edit_pages_render_for_a_company_member(): void
    {
        $offer = Offer::factory()->for($this->company)->create();

        $this->actingAs($this->member)->get('/ofertas')->assertOk();
        $this->actingAs($this->member)->get('/ofertas/crear')->assertOk();
        $this->actingAs($this->member)->get("/ofertas/{$offer->id}/editar")->assertOk();
    }

    public function test_a_company_member_can_create_a_draft_offer(): void
    {
        $response = $this->actingAs($this->member)->post('/ofertas', [
            'material' => MaterialType::Cardboard->value,
            'quantity_tons' => 50,
            'price_per_ton' => 420.50,
            'generation_year' => (int) date('Y'),
            'province' => 'Sevilla',
        ]);

        $response->assertRedirect('/ofertas');
        $offer = Offer::first();
        $this->assertSame(OfferStatus::Draft, $offer->status);
        $this->assertSame($this->company->id, $offer->company_id);
        $this->assertSame($this->member->id, $offer->created_by);
    }

    public function test_a_non_primary_company_member_can_create_and_list_offers(): void
    {
        $nonPrimaryMember = User::factory()->create();
        $this->company->users()->attach($nonPrimaryMember, ['is_primary' => false]);

        $response = $this->actingAs($nonPrimaryMember)->post('/ofertas', [
            'material' => MaterialType::Cardboard->value,
            'quantity_tons' => 50,
            'price_per_ton' => 420.50,
            'generation_year' => (int) date('Y'),
            'province' => 'Sevilla',
        ]);

        $response->assertRedirect('/ofertas');
        $offer = Offer::first();
        $this->assertSame($this->company->id, $offer->company_id);

        $this->actingAs($nonPrimaryMember)
            ->get('/ofertas')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('offers', 1));
    }

    public function test_a_user_from_another_company_cannot_edit_someone_elses_offer(): void
    {
        $offer = Offer::factory()->for($this->company)->create();
        $outsider = User::factory()->create();
        $otherCompany = Company::factory()->create();
        $otherCompany->users()->attach($outsider, ['is_primary' => true]);

        $response = $this->actingAs($outsider)->put("/ofertas/{$offer->id}", [
            'material' => MaterialType::Plastic->value,
            'quantity_tons' => 10,
            'price_per_ton' => 100,
            'generation_year' => (int) date('Y'),
            'province' => 'Toledo',
        ]);

        $response->assertForbidden();
    }

    public function test_publishing_sets_status_and_published_at(): void
    {
        $offer = Offer::factory()->for($this->company)->create();

        (new PublishOfferAction)->handle($offer);

        $this->assertSame(OfferStatus::Published, $offer->status);
        $this->assertNotNull($offer->published_at);
    }

    public function test_a_draft_offer_cannot_be_paused_directly(): void
    {
        $offer = Offer::factory()->for($this->company)->create();

        $this->expectException(RuntimeException::class);

        (new PauseOfferAction)->handle($offer);
    }

    public function test_pausing_and_republishing_a_published_offer(): void
    {
        $offer = Offer::factory()->for($this->company)->published()->create();

        (new PauseOfferAction)->handle($offer);
        $this->assertSame(OfferStatus::Paused, $offer->status);

        (new PublishOfferAction)->handle($offer);
        $this->assertSame(OfferStatus::Published, $offer->status);
    }

    public function test_closing_is_final(): void
    {
        $offer = Offer::factory()->for($this->company)->published()->create();
        $action = new CloseOfferAction(new RejectOrderAction(new SendMessageAction));

        $action->handle($this->member, $offer);
        $this->assertSame(OfferStatus::Closed, $offer->status);
        $this->assertNotNull($offer->closed_at);

        $this->expectException(RuntimeException::class);
        $action->handle($this->member, $offer);
    }

    public function test_closing_an_offer_auto_rejects_its_pending_orders(): void
    {
        $offer = Offer::factory()->for($this->company)->published()->create(['quantity_tons' => 100]);
        $buyerCompany = Company::factory()->create();
        $pendingOrder = Order::factory()->for($offer)->create([
            'buyer_company_id' => $buyerCompany->id,
            'quantity_tons' => 10,
        ]);

        (new CloseOfferAction(new RejectOrderAction(new SendMessageAction)))->handle($this->member, $offer);

        $this->assertSame(OrderStatus::Rejected, $pendingOrder->refresh()->status);
        $this->assertSame($this->member->id, $pendingOrder->responded_by);
    }

    public function test_closing_an_offer_over_http_auto_rejects_its_pending_orders(): void
    {
        $offer = Offer::factory()->for($this->company)->published()->create(['quantity_tons' => 100]);
        $buyerCompany = Company::factory()->create();
        $pendingOrder = Order::factory()->for($offer)->create([
            'buyer_company_id' => $buyerCompany->id,
            'quantity_tons' => 10,
        ]);

        $this->actingAs($this->member)->post("/ofertas/{$offer->id}/cerrar")->assertRedirect();

        $this->assertSame(OrderStatus::Rejected, $pendingOrder->refresh()->status);
    }

    public function test_closing_a_closed_offer_via_publish_fails(): void
    {
        $offer = Offer::factory()->for($this->company)->closed()->create();

        $this->expectException(RuntimeException::class);

        (new PublishOfferAction)->handle($offer);
    }

    public function test_status_is_not_mass_assignable_through_the_update_action(): void
    {
        $offer = Offer::factory()->for($this->company)->create();

        (new UpdateOfferAction)->handle($offer, [
            'status' => OfferStatus::Published->value,
            'province' => 'Cuenca',
        ]);

        $offer->refresh();
        $this->assertSame(OfferStatus::Draft, $offer->status);
        $this->assertSame('Cuenca', $offer->province);
    }
}
