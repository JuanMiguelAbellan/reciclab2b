<?php

namespace App\Actions\Orders;

use App\Actions\Conversations\StartConversationAction;
use App\Enums\OrderStatus;
use App\Models\Conversation;
use App\Models\Offer;
use App\Models\Order;
use App\Models\User;
use RuntimeException;

class PlaceOrderAction
{
    public function __construct(
        private readonly StartConversationAction $startConversation,
    ) {}

    /**
     * Place a purchase request for an offer. If no conversation is given
     * (e.g. placed straight from the offer page rather than from within an
     * existing thread), one is started/reused so both parties always have
     * somewhere to discuss the order.
     */
    public function handle(User $buyer, Offer $offer, float $quantityTons, ?Conversation $conversation = null): Order
    {
        if (! $offer->isPublished()) {
            throw new RuntimeException('Solo se puede pedir sobre una oferta publicada.');
        }

        if ($quantityTons <= 0 || $quantityTons > $offer->availableQuantity()) {
            throw new RuntimeException('La cantidad solicitada supera lo disponible.');
        }

        $buyerCompany = $buyer->company();

        $conversation ??= $this->startConversation->handle($buyer, $offer);

        $order = new Order(['quantity_tons' => $quantityTons]);
        $order->offer_id = $offer->id;
        $order->buyer_company_id = $buyerCompany->id;
        $order->conversation_id = $conversation->id;
        $order->created_by = $buyer->id;
        $order->price_per_ton = $offer->price_per_ton;
        $order->status = OrderStatus::Pending;
        $order->save();

        return $order;
    }
}
