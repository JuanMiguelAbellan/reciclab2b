<?php

namespace App\Actions\Conversations;

use App\Models\Conversation;
use App\Models\ConversationRead;
use App\Models\User;

class MarkConversationAsReadAction
{
    /**
     * Record the latest message the user has seen, so the unread indicator
     * only lights up for messages that arrive after this point. Uses the
     * message id (not a timestamp) as the watermark — deterministic even
     * when a read and a new message happen within the same instant.
     */
    public function handle(User $user, Conversation $conversation): void
    {
        $latestMessageId = $conversation->messages()->max('id');

        $read = ConversationRead::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->first();

        if ($read === null) {
            $read = new ConversationRead;
            $read->conversation_id = $conversation->id;
            $read->user_id = $user->id;
        }

        $read->last_read_message_id = $latestMessageId;
        $read->save();
    }
}
