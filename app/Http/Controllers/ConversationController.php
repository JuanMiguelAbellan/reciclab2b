<?php

namespace App\Http\Controllers;

use App\Actions\Conversations\MarkConversationAsReadAction;
use App\Actions\Conversations\SendMessageAction;
use App\Actions\Conversations\StartConversationAction;
use App\Http\Requests\SendMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Offer;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ConversationController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Conversation::class);

        $companyIds = $request->user()->companies()->pluck('companies.id');
        $unreadIds = $request->user()->unreadConversationIds();

        $conversations = Conversation::query()
            ->where(function ($query) use ($companyIds) {
                $query->whereIn('buyer_company_id', $companyIds)
                    ->orWhereHas('offer', fn ($q) => $q->whereIn('company_id', $companyIds));
            })
            ->with(['offer.company', 'buyerCompany'])
            ->withCount('messages')
            ->latest('updated_at')
            ->get();

        return Inertia::render('conversations/Index', [
            'conversations' => $conversations->map(fn (Conversation $conversation) => [
                'id' => $conversation->id,
                'offer_crop' => $conversation->offer->material->label(),
                'counterpart_name' => $this->counterpartName($conversation, $companyIds),
                'messages_count' => $conversation->messages_count,
                'unread' => $unreadIds->contains($conversation->id),
                'updated_at' => $conversation->updated_at->toIso8601String(),
            ]),
        ]);
    }

    public function store(Offer $offer, StartConversationAction $action): RedirectResponse
    {
        Gate::authorize('startFor', [Conversation::class, $offer]);

        $conversation = $action->handle(request()->user(), $offer);

        return redirect()->route('conversations.show', $conversation);
    }

    public function show(Request $request, Conversation $conversation, MarkConversationAsReadAction $markAsRead): Response
    {
        Gate::authorize('view', $conversation);

        $conversation->load(['offer.company', 'buyerCompany', 'messages.sender']);

        $markAsRead->handle($request->user(), $conversation);

        $companyIds = $request->user()->companies()->pluck('companies.id');

        return Inertia::render('conversations/Show', [
            'conversation' => [
                'id' => $conversation->id,
                'offer' => [
                    'id' => $conversation->offer->id,
                    'material' => $conversation->offer->material->label(),
                ],
                'counterpart_name' => $this->counterpartName($conversation, $companyIds),
                'canOrder' => $request->user()->can('placeFor', [Order::class, $conversation->offer]),
                'availableQuantity' => $conversation->offer->availableQuantity(),
            ],
            'messages' => $conversation->messages->map(fn (Message $message) => [
                'id' => $message->id,
                'body' => $message->body,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender->full_name,
                'is_mine' => $message->sender_id === $request->user()->id,
                'created_at' => $message->created_at->toIso8601String(),
            ]),
        ]);
    }

    public function storeMessage(SendMessageRequest $request, Conversation $conversation, SendMessageAction $action): RedirectResponse
    {
        $action->handle($request->user(), $conversation, $request->validated()['body']);

        return back();
    }

    /**
     * @param  Collection<int, int>  $viewerCompanyIds
     */
    private function counterpartName(Conversation $conversation, $viewerCompanyIds): string
    {
        if ($viewerCompanyIds->contains($conversation->buyer_company_id)) {
            return $conversation->offer->company->trade_name;
        }

        return $conversation->buyerCompany->trade_name;
    }
}
