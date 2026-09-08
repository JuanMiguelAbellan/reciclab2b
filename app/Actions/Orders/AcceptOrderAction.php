<?php

namespace App\Actions\Orders;

use App\Actions\Conversations\SendMessageAction;
use App\Actions\Offers\CloseOfferAction;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use RuntimeException;

class AcceptOrderAction
{
    public function __construct(
        private readonly CloseOfferAction $closeOffer,
        private readonly SendMessageAction $sendMessage,
    ) {}

    /**
     * Accept a pending order. Availability is re-checked here (not just at
     * request time) so two buyers racing for the same limited quantity
     * can't both get accepted. If accepting empties the offer, it's closed
     * automatically — closing is final, so a later cancellation of this
     * order will not reopen it (see RuntimeException docblock on
     * CancelOrderAction).
     */
    public function handle(User $seller, Order $order): Order
    {
        if (! $order->isPending()) {
            throw new RuntimeException('Solo se pueden aceptar pedidos pendientes.');
        }

        $offer = $order->offer;

        if ((float) $order->quantity_tons > $offer->availableQuantity()) {
            throw new RuntimeException('Ya no queda suficiente cantidad disponible para aceptar este pedido.');
        }

        $order->status = OrderStatus::Accepted;
        $order->responded_by = $seller->id;
        $order->responded_at = now();
        $order->save();

        if ($order->conversation_id !== null) {
            $this->sendMessage->handle(
                $seller,
                $order->conversation,
                "✅ Pedido aceptado: {$order->quantity_tons} t a {$order->price_per_ton} €/t.",
            );
        }

        if (! $offer->isClosed() && $offer->availableQuantity() <= 0.0) {
            $this->closeOffer->handle($seller, $offer);
        }

        return $order;
    }
}
