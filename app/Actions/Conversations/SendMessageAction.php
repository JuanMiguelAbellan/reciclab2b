<?php

namespace App\Actions\Conversations;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;

class SendMessageAction
{
    public function handle(User $sender, Conversation $conversation, string $body): Message
    {
        $message = new Message(['body' => $body]);
        $message->conversation_id = $conversation->id;
        $message->sender_id = $sender->id;
        $message->save();

        MessageSent::dispatch($message);

        return $message;
    }
}
