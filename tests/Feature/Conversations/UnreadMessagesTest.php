<?php

namespace Tests\Feature\Conversations;

use App\Actions\Conversations\SendMessageAction;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnreadMessagesTest extends TestCase
{
    use RefreshDatabase;

    protected Company $seller;

    protected User $sellerMember;

    protected Company $buyerCompany;

    protected User $buyer;

    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = Company::factory()->create();
        $this->sellerMember = User::factory()->create();
        $this->seller->users()->attach($this->sellerMember, ['is_primary' => true]);

        $offer = Offer::factory()->for($this->seller)->published()->create();

        $this->buyerCompany = Company::factory()->create();
        $this->buyer = User::factory()->create();
        $this->buyerCompany->users()->attach($this->buyer, ['is_primary' => true]);

        $this->conversation = Conversation::factory()->create([
            'offer_id' => $offer->id,
            'buyer_company_id' => $this->buyerCompany->id,
        ]);
    }

    public function test_a_new_message_makes_the_conversation_unread_for_the_recipient_only(): void
    {
        (new SendMessageAction)->handle($this->buyer, $this->conversation, 'Hola');

        $this->assertTrue($this->sellerMember->unreadConversationIds()->contains($this->conversation->id));
        $this->assertFalse($this->buyer->unreadConversationIds()->contains($this->conversation->id));
    }

    public function test_visiting_the_conversation_marks_it_as_read(): void
    {
        (new SendMessageAction)->handle($this->buyer, $this->conversation, 'Hola');
        $this->assertSame(1, $this->sellerMember->unreadConversationsCount());

        $this->actingAs($this->sellerMember)->get("/mensajes/{$this->conversation->id}")->assertOk();

        $this->sellerMember->refresh();
        $this->assertSame(0, $this->sellerMember->unreadConversationsCount());
    }

    public function test_a_message_sent_after_reading_makes_it_unread_again(): void
    {
        (new SendMessageAction)->handle($this->buyer, $this->conversation, 'Primero');
        $this->actingAs($this->sellerMember)->get("/mensajes/{$this->conversation->id}");
        $this->assertSame(0, $this->sellerMember->unreadConversationsCount());

        (new SendMessageAction)->handle($this->buyer, $this->conversation, 'Segundo');
        $this->assertSame(1, $this->sellerMember->unreadConversationsCount());
    }

    public function test_the_unread_count_covers_multiple_conversations(): void
    {
        $otherOffer = Offer::factory()->for($this->seller)->published()->create();
        $otherConversation = Conversation::factory()->create([
            'offer_id' => $otherOffer->id,
            'buyer_company_id' => $this->buyerCompany->id,
        ]);

        (new SendMessageAction)->handle($this->buyer, $this->conversation, 'Uno');
        (new SendMessageAction)->handle($this->buyer, $otherConversation, 'Dos');

        $this->assertSame(2, $this->sellerMember->unreadConversationsCount());
    }

    public function test_the_shared_inertia_prop_reflects_the_unread_count(): void
    {
        (new SendMessageAction)->handle($this->buyer, $this->conversation, 'Hola');

        $this->actingAs($this->sellerMember)
            ->get('/mensajes')
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.unread_conversations_count', 1)
                ->where('conversations.0.unread', true)
            );
    }
}
