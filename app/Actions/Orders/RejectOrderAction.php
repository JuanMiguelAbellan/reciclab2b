<?php

namespace App\Actions\Orders;

use App\Actions\Conversations\SendMessageAction;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use RuntimeException;

class RejectOrderAction
{
    public function __construct(
        private readonly SendMessageAction $sendMessage,
    ) {}

    public function handle(User $seller, Order $order, ?string $note = null): Order
    {
        if (! $order->isPending()) {
            throw new RuntimeException('Solo se pueden rechazar pedidos pendientes.');
        }

        $order->status = OrderStatus::Rejected;
        $order->responded_by = $seller->id;
        $order->responded_at = now();
        $order->save();

        if ($order->conversation_id !== null) {
            $this->sendMessage->handle(
                $seller,
                $order->conversation,
                $note ?? "❌ Pedido rechazado: {$order->quantity_tons} t a {$order->price_per_ton} €/t.",
            );
        }

        return $order;
    }
}
