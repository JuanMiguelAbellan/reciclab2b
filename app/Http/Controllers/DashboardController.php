<?php

namespace App\Http\Controllers;

use App\Enums\OfferStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $company = $user->company();

        if ($company === null) {
            return Inertia::render('Dashboard', [
                'company' => null,
            ]);
        }

        $pendingOrders = Order::query()
            ->whereHas('offer', fn ($query) => $query->where('company_id', $company->id))
            ->where('status', OrderStatus::Pending)
            ->with(['offer', 'buyerCompany'])
            ->latest()
            ->get();

        return Inertia::render('Dashboard', [
            'company' => [
                'trade_name' => $company->trade_name,
                'status' => ['value' => $company->status->value, 'label' => $company->status->label()],
            ],
            'stats' => [
                'active_offers' => $company->offers()->whereIn('status', [OfferStatus::Published, OfferStatus::Paused])->count(),
                'pending_orders' => $pendingOrders->count(),
                'unread_conversations' => $user->unreadConversationsCount(),
            ],
            'pendingOrders' => $pendingOrders->take(5)->map(fn (Order $order) => [
                'id' => $order->id,
                'buyer_name' => $order->buyerCompany->trade_name,
                'material' => $order->offer->material->label(),
                'quantity_tons' => (float) $order->quantity_tons,
                'total' => round((float) $order->quantity_tons * (float) $order->price_per_ton, 2),
            ]),
        ]);
    }
}
