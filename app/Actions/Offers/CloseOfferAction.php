<?php

namespace App\Actions\Offers;

use App\Actions\Orders\RejectOrderAction;
use App\Enums\OfferStatus;
use App\Enums\OrderStatus;
use App\Models\Offer;
use App\Models\Order;
use App\Models\User;
use RuntimeException;

class CloseOfferAction
{
    public function __construct(
        private readonly RejectOrderAction $rejectOrder,
    ) {}

    /**
     * Close an offer for good (e.g. it sold out or is no longer available).
     * Closing is final: a closed offer cannot be republished.
     *
     * Any order still pending on this offer has no way to be fulfilled
     * once it's closed, so it's auto-rejected rather than left dangling —
     * otherwise it could sit forever, or worse, still get accepted later
     * against an offer that's supposedly done.
     */
    public function handle(User $user, Offer $offer): Offer
    {
        if ($offer->status === OfferStatus::Closed) {
            throw new RuntimeException('This offer is already closed.');
        }

        $offer->status = OfferStatus::Closed;
        $offer->closed_at = now();
        $offer->save();

        $offer->orders()
            ->where('status', OrderStatus::Pending)
            ->get()
            ->each(fn (Order $order) => $this->rejectOrder->handle(
                $user,
                $order,
                '❌ Pedido rechazado automáticamente: la oferta se ha cerrado.',
            ));

        return $offer;
    }
}
