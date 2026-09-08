<?php

namespace App\Actions\Orders;

use App\Actions\Conversations\SendMessageAction;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use RuntimeException;

class CompleteOrderAction
{
    public function __construct(
        private readonly SendMessageAction $sendMessage,
    ) {}

    public function handle(User $seller, Order $order): Order
    {
        if (! $order->isAccepted()) {
            throw new RuntimeException('Solo se pueden completar pedidos aceptados.');
        }

        $order->status = OrderStatus::Completed;
        $order->completed_at = now();
        $order->save();

        if ($order->conversation_id !== null) {
            $this->sendMessage->handle($seller, $order->conversation, '📦 Pedido marcado como completado.');
        }

        return $order;
    }
}
