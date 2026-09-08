<?php

namespace Tests\Feature\Conversations;

use App\Actions\Conversations\SendMessageAction;
use App\Events\MessageSent;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_message_broadcasts_on_the_conversation_and_every_other_participants_personal_channel(): void
    {
        $seller = Company::factory()->create();
        $sellerMember = User::factory()->create();
        $seller->users()->attach($sellerMember, ['is_primary' => true]);

        $offer = Offer::factory()->for($seller)->published()->create();

        $buyerCompany = Company::factory()->create();
        $buyer = User::factory()->create();
        $secondBuyerMember = User::factory()->create();
        $buyerCompany->users()->attach($buyer, ['is_primary' => true]);
        $buyerCompany->users()->attach($secondBuyerMember, ['is_primary' => false]);

        $conversation = Conversation::factory()->create([
            'offer_id' => $offer->id,
            'buyer_company_id' => $buyerCompany->id,
        ]);

        $message = (new SendMessageAction)->handle($buyer, $conversation, 'Hola');

        $channels = (new MessageSent($message))->broadcastOn();
        $channelNames = collect($channels)->map(fn ($channel) => $channel->name)->all();

        $this->assertContains("private-conversation.{$conversation->id}", $channelNames);
        $this->assertContains("private-App.Models.User.{$sellerMember->id}", $channelNames);
        $this->assertContains("private-App.Models.User.{$secondBuyerMember->id}", $channelNames);
        $this->assertNotContains("private-App.Models.User.{$buyer->id}", $channelNames);
    }
}
