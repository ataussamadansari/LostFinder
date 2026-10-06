<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->message->conversation->uuid),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $this->message->loadMissing(['sender', 'attachments.media']);

        $attachments = $this->message->attachments->map(function ($att) {
            return [
                'media_uuid' => $att->media?->uuid,
                'file_name' => $att->media?->original_name,
                'mime_type' => $att->media?->mime_type,
                'file_size' => $att->media?->size,
            ];
        });

        return [
            'id' => $this->message->id,
            'conversation_uuid' => $this->message->conversation->uuid,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender?->name ?? 'User',
            'message_type' => $this->message->message_type,
            'body' => $this->message->body,
            'sent_at' => $this->message->sent_at?->toIso8601String() ?? now()->toIso8601String(),
            'attachments' => $attachments,
        ];
    }
}
