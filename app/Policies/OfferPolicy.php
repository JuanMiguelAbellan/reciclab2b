<?php

namespace App\Policies;

use App\Enums\OfferStatus;
use App\Enums\RoleName;
use App\Models\Offer;
use App\Models\User;

class OfferPolicy
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
    public function view(User $user, Offer $offer): bool
    {
        if ($user->hasAnyRole([RoleName::SuperAdmin->value, RoleName::Admin->value])) {
            return true;
        }

        return $user->companies()->whereKey($offer->company_id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isApproved() && ($user->company()?->isApproved() ?? false);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Offer $offer): bool
    {
        if ($offer->status === OfferStatus::Closed) {
            return false;
        }

        return $this->manages($user, $offer);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Offer $offer): bool
    {
        return $offer->status === OfferStatus::Draft && $this->manages($user, $offer);
    }

    /**
     * Determine whether the user can publish, pause or close the model.
     */
    public function manage(User $user, Offer $offer): bool
    {
        return $this->manages($user, $offer);
    }

    /**
     * Determine whether the user can see the offer's exact (private)
     * coordinates, as opposed to the displaced public ones.
     */
    public function viewExactLocation(User $user, Offer $offer): bool
    {
        return $this->manages($user, $offer);
    }

    private function manages(User $user, Offer $offer): bool
    {
        if ($user->hasAnyRole([RoleName::SuperAdmin->value, RoleName::Admin->value])) {
            return true;
        }

        return $user->companies()->whereKey($offer->company_id)->exists();
    }
}
