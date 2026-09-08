<?php

namespace App\Actions\Offers;

use App\Enums\OfferStatus;
use App\Models\Company;
use App\Models\Offer;
use App\Models\User;

class CreateOfferAction
{
    /**
     * Create a new offer for the user's company. New offers always start as
     * a draft so the producer can review them before publishing.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, Company $company, array $data): Offer
    {
        $offer = new Offer($data);
        $offer->company_id = $company->id;
        $offer->created_by = $user->id;
        $offer->status = OfferStatus::Draft;
        $offer->refreshPublicLocation();
        $offer->save();

        return $offer;
    }
}
