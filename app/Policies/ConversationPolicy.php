<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Conversation;
use App\Models\Offer;
use App\Models\User;

class ConversationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isApproved();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        if ($user->hasAnyRole([RoleName::SuperAdmin->value, RoleName::Admin->value])) {
            return true;
        }

        return $conversation->involves($user);
    }

    /**
     * Determine whether the user can start a conversation about the given offer.
     */
    public function startFor(User $user, Offer $offer): bool
    {
        if (! $offer->isTradeable()) {
            return false;
        }

        $company = $user->company();

        if ($company === null || ! $company->isApproved()) {
            return false;
        }

        // A company cannot open a conversation with itself about its own offer.
        return $company->id !== $offer->company_id;
    }

    /**
     * Determine whether the user can send a message in the conversation.
     */
    public function sendMessage(User $user, Conversation $conversation): bool
    {
        return $conversation->involves($user);
    }
}
