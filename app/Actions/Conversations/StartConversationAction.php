<?php

namespace App\Actions\Conversations;

use App\Models\Conversation;
use App\Models\Offer;
use App\Models\User;

class StartConversationAction
{
    /**
     * Find the existing conversation between the user's company and the
     * offer's company, or start a new one. One conversation per offer per
     * buyer company, so clicking "Contactar" again reuses the same thread.
     */
    public function handle(User $user, Offer $offer): Conversation
    {
        $buyerCompany = $user->company();

        $existing = Conversation::where('offer_id', $offer->id)
            ->where('buyer_company_id', $buyerCompany->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $conversation = new Conversation;
        $conversation->offer_id = $offer->id;
        $conversation->buyer_company_id = $buyerCompany->id;
        $conversation->save();

        return $conversation;
    }
}
