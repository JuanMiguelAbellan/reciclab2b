<?php

namespace Tests\Feature\Conversations;

use App\Events\MessageSent;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ConversationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Company $seller;

    protected Offer $offer;

    protected Company $buyerCompany;

    protected User $buyer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = Company::factory()->create();
        $this->offer = Offer::factory()->for($this->seller)->published()->create();

        $this->buyerCompany = Company::factory()->create();
        $this->buyer = User::factory()->create();
        $this->buyerCompany->users()->attach($this->buyer, ['is_primary' => true]);
    }

    public function test_clicking_contact_creates_a_conversation_and_redirects_to_it(): void
    {
        $response = $this->actingAs($this->buyer)->post("/mercado/{$this->offer->id}/contactar");

        $conversation = Conversation::first();
        $response->assertRedirect("/mensajes/{$conversation->id}");
        $this->assertSame($this->offer->id, $conversation->offer_id);
        $this->assertSame($this->buyerCompany->id, $conversation->buyer_company_id);
    }

    public function test_contacting_the_same_offer_twice_reuses_the_conversation(): void
    {
        $this->actingAs($this->buyer)->post("/mercado/{$this->offer->id}/contactar");
        $this->actingAs($this->buyer)->post("/mercado/{$this->offer->id}/contactar");

        $this->assertSame(1, Conversation::count());
    }

    public function test_a_seller_cannot_contact_their_own_offer(): void
    {
        $sellerMember = User::factory()->create();
        $this->seller->users()->attach($sellerMember, ['is_primary' => true]);

        $this->actingAs($sellerMember)
            ->post("/mercado/{$this->offer->id}/contactar")
            ->assertForbidden();
    }

    public function test_participants_can_exchange_messages_and_an_event_is_broadcast(): void
    {
        Event::fake([MessageSent::class]);

        $conversation = Conversation::factory()->create([
            'offer_id' => $this->offer->id,
            'buyer_company_id' => $this->buyerCompany->id,
        ]);

        $response = $this->actingAs($this->buyer)->post("/mensajes/{$conversation->id}/mensajes", [
            'body' => 'Hola, ¿sigue disponible?',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $this->buyer->id,
            'body' => 'Hola, ¿sigue disponible?',
        ]);
        Event::assertDispatched(MessageSent::class);
    }

    public function test_an_outsider_cannot_send_messages_in_someone_elses_conversation(): void
    {
        $conversation = Conversation::factory()->create([
            'offer_id' => $this->offer->id,
            'buyer_company_id' => $this->buyerCompany->id,
        ]);
        $outsider = User::factory()->create();
        Company::factory()->create()->users()->attach($outsider, ['is_primary' => true]);

        $this->actingAs($outsider)
            ->post("/mensajes/{$conversation->id}/mensajes", ['body' => 'Colándome'])
            ->assertForbidden();
    }

    public function test_the_inbox_and_thread_pages_render(): void
    {
        $conversation = Conversation::factory()->create([
            'offer_id' => $this->offer->id,
            'buyer_company_id' => $this->buyerCompany->id,
        ]);

        $this->actingAs($this->buyer)->get('/mensajes')->assertOk();
        $this->actingAs($this->buyer)->get("/mensajes/{$conversation->id}")->assertOk();
    }
}
