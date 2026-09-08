<?php

namespace Tests\Feature\Conversations;

use App\Models\Company;
use App\Models\Conversation;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_approved_buyer_can_start_a_conversation_about_an_offer(): void
    {
        $seller = Company::factory()->create();
        $offer = Offer::factory()->for($seller)->published()->create();

        $buyerCompany = Company::factory()->create();
        $buyer = User::factory()->create();
        $buyerCompany->users()->attach($buyer, ['is_primary' => true]);

        $this->assertTrue($buyer->can('startFor', [Conversation::class, $offer]));
    }

    public function test_a_non_primary_member_of_the_buyer_company_can_start_a_conversation(): void
    {
        $seller = Company::factory()->create();
        $offer = Offer::factory()->for($seller)->published()->create();

        $buyerCompany = Company::factory()->create();
        $nonPrimaryBuyer = User::factory()->create();
        $buyerCompany->users()->attach($nonPrimaryBuyer, ['is_primary' => false]);

        $this->assertTrue($nonPrimaryBuyer->can('startFor', [Conversation::class, $offer]));
    }

    public function test_a_seller_cannot_start_a_conversation_about_their_own_offer(): void
    {
        $seller = Company::factory()->create();
        $offer = Offer::factory()->for($seller)->published()->create();

        $sellerMember = User::factory()->create();
        $seller->users()->attach($sellerMember, ['is_primary' => true]);

        $this->assertFalse($sellerMember->can('startFor', [Conversation::class, $offer]));
    }

    public function test_a_user_without_an_approved_company_cannot_start_a_conversation(): void
    {
        $seller = Company::factory()->create();
        $offer = Offer::factory()->for($seller)->published()->create();

        $buyer = User::factory()->create();

        $this->assertFalse($buyer->can('startFor', [Conversation::class, $offer]));
    }

    public function test_only_conversation_participants_can_view_it(): void
    {
        $seller = Company::factory()->create();
        $offer = Offer::factory()->for($seller)->published()->create();
        $buyerCompany = Company::factory()->create();
        $conversation = Conversation::factory()->create([
            'offer_id' => $offer->id,
            'buyer_company_id' => $buyerCompany->id,
        ]);

        $buyerMember = User::factory()->create();
        $buyerCompany->users()->attach($buyerMember, ['is_primary' => true]);

        $sellerMember = User::factory()->create();
        $seller->users()->attach($sellerMember, ['is_primary' => true]);

        $outsider = User::factory()->create();

        $this->assertTrue($buyerMember->can('view', $conversation));
        $this->assertTrue($sellerMember->can('view', $conversation));
        $this->assertFalse($outsider->can('view', $conversation));
    }
}
