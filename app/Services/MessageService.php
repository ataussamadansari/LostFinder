<?php

namespace App\Services;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MessageService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Get paginated conversations for a user.
     */
    public function getUserConversations(User $user, int $perPage = 15): LengthAwarePaginator
    {
        $query = Conversation::query()
            ->with(['participants', 'latestMessage.sender', 'ticket.item', 'journey.vehicle']);

        if (!$user->isAdmin() && !$user->hasPermission('lost_items.view')) {
            $query->whereHas('participants', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        return $query->orderBy('updated_at', 'desc')->paginate($perPage);
    }

    /**
     * Get a single conversation with participant authorization check.
     */
    public function getConversation(string $uuid, User $user): Conversation
    {
        $conversation = Conversation::where('uuid', $uuid)
            ->with(['participants', 'ticket.item', 'journey.vehicle', 'journey.driver.user'])
            ->first();

        if (!$conversation) {
            abort(404, 'Conversation not found.');
        }

        $this->authorizeParticipant($conversation, $user);

        return $conversation;
    }

    /**
     * Get paginated messages for a conversation.
     */
    public function getConversationMessages(Conversation $conversation, User $user, int $perPage = 30): LengthAwarePaginator
    {
        $this->authorizeParticipant($conversation, $user);

        return Message::where('conversation_id', $conversation->id)
            ->with(['sender', 'attachments.media'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Send a message in a conversation.
     */
    public function sendMessage(Conversation $conversation, User $sender, array $data): Message
    {
        $this->authorizeParticipant($conversation, $sender);

        if ($conversation->status !== 'active') {
            throw new \DomainException("Cannot send message. This conversation is {$conversation->status}.");
        }

        $messageType = $data['message_type'] ?? 'text';
        $body = trim($data['body'] ?? '');

        if ($messageType === 'text' && empty($body)) {
            throw new \DomainException('Message body cannot be empty.');
        }

        return DB::transaction(function () use ($conversation, $sender, $data, $messageType, $body) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'message_type' => $messageType,
                'body' => $body ?: null,
                'sent_at' => now(),
            ]);

            if (!empty($data['media_id'])) {
                MessageAttachment::create([
                    'message_id' => $message->id,
                    'media_id' => $data['media_id'],
                    'created_at' => now(),
                ]);
            }

            $conversation->touch();

            $message->load(['sender', 'attachments.media', 'conversation']);

            // Broadcast message via WebSockets
            broadcast(new MessageSent($message))->toOthers();

            // Dispatch push notification to other participants
            $recipients = $conversation->participants()
                ->where('users.id', '!=', $sender->id)
                ->get();

            foreach ($recipients as $recipient) {
                $notificationBody = ($messageType === 'image')
                    ? 'Shared an image.'
                    : (strlen($body) > 60 ? substr($body, 0, 57) . '...' : $body);

                $this->notificationService->notifyUser(
                    $recipient,
                    'new_message',
                    'New Message',
                    $notificationBody,
                    [
                        'conversation_uuid' => $conversation->uuid,
                        'ticket_id' => (string) $conversation->ticket_id,
                        'sender_id' => (string) $sender->id,
                    ]
                );
            }

            return $message;
        });
    }

    /**
     * Mark unread messages in conversation as read for the current user.
     */
    public function markMessagesAsRead(Conversation $conversation, User $reader): int
    {
        $this->authorizeParticipant($conversation, $reader);

        $updatedCount = Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $reader->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($updatedCount > 0) {
            broadcast(new MessageRead($conversation, $reader))->toOthers();
        }

        return $updatedCount;
    }

    /**
     * Authorize that user is a participant or staff.
     */
    protected function authorizeParticipant(Conversation $conversation, User $user): void
    {
        if ($user->isAdmin() || $user->hasPermission('lost_items.view')) {
            return;
        }

        $isParticipant = $conversation->participants()->where('users.id', $user->id)->exists();
        if (!$isParticipant) {
            abort(403, 'Unauthorized. You are not a participant in this conversation.');
        }
    }
}
