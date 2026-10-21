<?php

namespace App\Actions\Offers;

use App\Enums\OfferStatus;
use App\Models\Offer;
use App\Services\Stats\StatsWebhookClient;
use RuntimeException;

class PublishOfferAction
{
    public function __construct(
        private readonly StatsWebhookClient $statsWebhook,
    ) {}

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

        $this->statsWebhook->offerPublished($offer);

        return $offer;
    }
}
