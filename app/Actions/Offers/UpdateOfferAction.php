<?php

namespace App\Actions\Offers;

use App\Models\Offer;

class UpdateOfferAction
{
    /**
     * Update an offer's editable fields. Status and its timestamps are
     * managed exclusively by the publish/pause/close actions.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Offer $offer, array $data): Offer
    {
        $offer->fill($data);
        $offer->refreshPublicLocation();
        $offer->save();

        return $offer;
    }
}
