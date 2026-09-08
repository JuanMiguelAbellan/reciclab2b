<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Offer;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
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
    public function view(User $user, Order $order): bool
    {
        return $this->isStaff($user) || $order->involves($user);
    }

    /**
     * Determine whether the user can place an order for the given offer.
     */
    public function placeFor(User $user, Offer $offer): bool
    {
        if (! $offer->isTradeable()) {
            return false;
        }

        $company = $user->company();

        if ($company === null || ! $company->isApproved()) {
            return false;
        }

        // A company cannot order its own offer.
        return $company->id !== $offer->company_id;
    }

    /**
     * Determine whether the user can accept or reject the order (the
     * seller's side only, while it's still pending).
     */
    public function respond(User $user, Order $order): bool
    {
        if (! $order->isPending()) {
            return false;
        }

        return $this->isStaff($user) || $user->companies()->whereKey($order->sellerCompanyId())->exists();
    }

    /**
     * Determine whether the user can mark an accepted order as completed
     * (the seller's side only).
     */
    public function complete(User $user, Order $order): bool
    {
        if (! $order->isAccepted()) {
            return false;
        }

        return $this->isStaff($user) || $user->companies()->whereKey($order->sellerCompanyId())->exists();
    }

    /**
     * Determine whether the user can cancel the order: the buyer while it's
     * pending (withdrawing the request), or either party once it's been
     * accepted (the deal falls through before completion).
     */
    public function cancel(User $user, Order $order): bool
    {
        if ($this->isStaff($user)) {
            return $order->isPending() || $order->isAccepted();
        }

        if ($order->isPending()) {
            return $user->companies()->whereKey($order->buyer_company_id)->exists();
        }

        if ($order->isAccepted()) {
            return $order->involves($user);
        }

        return false;
    }

    private function isStaff(User $user): bool
    {
        return $user->hasAnyRole([RoleName::SuperAdmin->value, RoleName::Admin->value]);
    }
}
