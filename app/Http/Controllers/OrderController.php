<?php

namespace App\Http\Controllers;

use App\Actions\Orders\AcceptOrderAction;
use App\Actions\Orders\CancelOrderAction;
use App\Actions\Orders\CompleteOrderAction;
use App\Actions\Orders\PlaceOrderAction;
use App\Actions\Orders\RejectOrderAction;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Conversation;
use App\Models\Offer;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Order::class);

        $companyIds = $request->user()->companies()->pluck('companies.id');

        $orders = Order::query()
            ->where(function ($query) use ($companyIds) {
                $query->whereIn('buyer_company_id', $companyIds)
                    ->orWhereHas('offer', fn ($q) => $q->whereIn('company_id', $companyIds));
            })
            ->with(['offer.company', 'buyerCompany'])
            ->latest()
            ->get();

        return Inertia::render('orders/Index', [
            'orders' => $orders->map(fn (Order $order) => $this->present($order, $companyIds)),
        ]);
    }

    public function storeForOffer(StoreOrderRequest $request, Offer $offer, PlaceOrderAction $action): RedirectResponse
    {
        $order = $action->handle($request->user(), $offer, (float) $request->validated()['quantity_tons']);

        return redirect()->route('orders.show', $order)->with('success', 'Pedido enviado.');
    }

    public function storeForConversation(StoreOrderRequest $request, Conversation $conversation, PlaceOrderAction $action): RedirectResponse
    {
        Gate::authorize('view', $conversation);

        $order = $action->handle(
            $request->user(),
            $conversation->offer,
            (float) $request->validated()['quantity_tons'],
            $conversation,
        );

        return redirect()->route('orders.show', $order)->with('success', 'Pedido enviado.');
    }

    public function show(Request $request, Order $order): Response
    {
        Gate::authorize('view', $order);

        $order->load(['offer.company', 'buyerCompany', 'createdBy', 'respondedBy', 'cancelledBy']);

        $companyIds = $request->user()->companies()->pluck('companies.id');

        return Inertia::render('orders/Show', [
            'order' => [
                ...$this->present($order, $companyIds),
                'offer_id' => $order->offer_id,
                'conversation_id' => $order->conversation_id,
                'created_by_name' => $order->createdBy->full_name,
                'responded_by_name' => $order->respondedBy?->full_name,
                'cancelled_by_name' => $order->cancelledBy?->full_name,
                'can_respond' => $request->user()->can('respond', $order),
                'can_complete' => $request->user()->can('complete', $order),
                'can_cancel' => $request->user()->can('cancel', $order),
            ],
        ]);
    }

    public function accept(Request $request, Order $order, AcceptOrderAction $action): RedirectResponse
    {
        Gate::authorize('respond', $order);

        $action->handle($request->user(), $order);

        return back()->with('success', 'Pedido aceptado.');
    }

    public function reject(Request $request, Order $order, RejectOrderAction $action): RedirectResponse
    {
        Gate::authorize('respond', $order);

        $action->handle($request->user(), $order);

        return back()->with('success', 'Pedido rechazado.');
    }

    public function complete(Request $request, Order $order, CompleteOrderAction $action): RedirectResponse
    {
        Gate::authorize('complete', $order);

        $action->handle($request->user(), $order);

        return back()->with('success', 'Pedido completado.');
    }

    public function cancel(Request $request, Order $order, CancelOrderAction $action): RedirectResponse
    {
        Gate::authorize('cancel', $order);

        $action->handle($request->user(), $order);

        return back()->with('success', 'Pedido cancelado.');
    }

    /**
     * @param  Collection<int, int>  $viewerCompanyIds
     * @return array<string, mixed>
     */
    private function present(Order $order, Collection $viewerCompanyIds): array
    {
        $isBuyer = $viewerCompanyIds->contains($order->buyer_company_id);

        return [
            'id' => $order->id,
            'offer' => [
                'id' => $order->offer->id,
                'material' => ['value' => $order->offer->material->value, 'label' => $order->offer->material->label()],
            ],
            'counterpart_name' => $isBuyer ? $order->offer->company->trade_name : $order->buyerCompany->trade_name,
            'role' => $isBuyer ? 'buyer' : 'seller',
            'quantity_tons' => (float) $order->quantity_tons,
            'price_per_ton' => (float) $order->price_per_ton,
            'total' => round((float) $order->quantity_tons * (float) $order->price_per_ton, 2),
            'status' => ['value' => $order->status->value, 'label' => $order->status->label()],
            'created_at' => $order->created_at->toIso8601String(),
        ];
    }
}
