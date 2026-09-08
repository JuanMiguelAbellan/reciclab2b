<?php

namespace App\Actions\Offers;

use App\Enums\OfferStatus;
use App\Models\Offer;
use RuntimeException;

class PauseOfferAction
{
    /**
     * Temporarily hide a published offer without closing it.
     */
    public function handle(Offer $offer): Offer
    {
        if ($offer->status !== OfferStatus::Published) {
            throw new RuntimeException("Cannot pause an offer with status [{$offer->status->value}].");
        }

        $offer->status = OfferStatus::Paused;
        $offer->save();

        return $offer;
    }
}
