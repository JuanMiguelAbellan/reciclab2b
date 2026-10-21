<?php

namespace App\Actions\Orders;

use App\Actions\Conversations\SendMessageAction;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\Stats\StatsWebhookClient;
use RuntimeException;

class CompleteOrderAction
{
    public function __construct(
        private readonly SendMessageAction $sendMessage,
        private readonly StatsWebhookClient $statsWebhook,
    ) {}

    public function handle(User $seller, Order $order): Order
    {
        if (! $order->isAccepted()) {
            throw new RuntimeException('Solo se pueden completar pedidos aceptados.');
        }

        $order->status = OrderStatus::Completed;
        $order->completed_at = now();
        $order->save();

        $this->statsWebhook->orderCompleted($order);

        if ($order->conversation_id !== null) {
            $this->sendMessage->handle($seller, $order->conversation, '📦 Pedido marcado como completado.');
        }

        return $order;
    }
}
