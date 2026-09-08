<?php

namespace App\Actions\Orders;

use App\Actions\Conversations\SendMessageAction;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use RuntimeException;

class CancelOrderAction
{
    public function __construct(
        private readonly SendMessageAction $sendMessage,
    ) {}

    /**
     * Cancel a pending or accepted order. Deliberately does not reopen the
     * offer even if accepting this order had auto-closed it — closing is
     * final elsewhere in this codebase (see CloseOfferAction), and
     * silently reopening here would contradict that. If the offer needs to
     * take orders again, the seller has to publish a new one.
     */
    public function handle(User $user, Order $order): Order
    {
        if (! in_array($order->status, [OrderStatus::Pending, OrderStatus::Accepted], true)) {
            throw new RuntimeException('Este pedido ya no se puede cancelar.');
        }

        $order->status = OrderStatus::Cancelled;
        $order->cancelled_by = $user->id;
        $order->cancelled_at = now();
        $order->save();

        if ($order->conversation_id !== null) {
            $this->sendMessage->handle(
                $user,
                $order->conversation,
                "🚫 Pedido cancelado: {$order->quantity_tons} t a {$order->price_per_ton} €/t.",
            );
        }

        return $order;
    }
}
