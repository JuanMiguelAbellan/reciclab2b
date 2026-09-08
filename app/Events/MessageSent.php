<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message,
    ) {}

    /**
     * Broadcast on the conversation thread itself (so an open chat updates
     * live) and on every other participant's personal channel (so their
     * unread badge updates live even when they're not looking at this
     * conversation). The sender is excluded — they don't need a ping about
     * their own message.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $conversation = $this->message->conversation;

        $recipientChannels = $conversation->buyerCompany->users
            ->merge($conversation->sellerCompany()->users)
            ->where('id', '!==', $this->message->sender_id)
            ->unique('id')
            ->map(fn ($user) => new PrivateChannel("App.Models.User.{$user->id}"))
            ->values()
            ->all();

        return [
            new PrivateChannel("conversation.{$this->message->conversation_id}"),
            ...$recipientChannels,
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'body' => $this->message->body,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender->full_name,
            'created_at' => $this->message->created_at->toIso8601String(),
        ];
    }
}
