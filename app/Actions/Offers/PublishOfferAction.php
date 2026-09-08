<?php

namespace App\Actions\Offers;

use App\Enums\OfferStatus;
use App\Models\Offer;
use RuntimeException;

class PublishOfferAction
{
    /**
     * Publish a draft or resume a paused offer.
     */
    public function handle(Offer $offer): Offer
    {
        if (! in_array($offer->status, [OfferStatus::Draft, OfferStatus::Paused], true)) {
            throw new RuntimeException("Cannot publish an offer with status [{$offer->status->value}].");
        }

        $offer->status = OfferStatus::Published;
        $offer->published_at = now();
        $offer->save();

        return $offer;
    }
}
