<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\MessageService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected MessageService $messageService
    ) {}

    /**
     * List user conversations.
     */
    public function index(Request $request): JsonResponse
    {
        $conversations = $this->messageService->getUserConversations(
            $request->user(),
            (int) $request->query('per_page', 15)
        );

        $formatted = collect($conversations->items())->map(function ($c) use ($request) {
            $otherParticipants = $c->participants
                ->where('id', '!=', $request->user()->id)
                ->map(fn($p) => ['id' => $p->id, 'name' => $p->name]);

            return [
                'uuid' => $c->uuid,
                'type' => $c->type,
                'status' => $c->status,
                'ticket_id' => $c->ticket_id,
                'journey_id' => $c->journey_id,
                'other_participants' => $otherParticipants->values(),
                'latest_message' => $c->latestMessage ? [
                    'id' => $c->latestMessage->id,
                    'message_type' => $c->latestMessage->message_type,
                    'body' => $c->latestMessage->body,
                    'sent_at' => $c->latestMessage->sent_at?->toIso8601String(),
                ] : null,
                'updated_at' => $c->updated_at?->toIso8601String(),
            ];
        });

        return $this->successResponse([
            'conversations' => $formatted,
            'pagination' => [
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
                'per_page' => $conversations->perPage(),
                'total' => $conversations->total(),
            ],
        ], 'Conversations retrieved successfully.');
    }

    /**
     * Get single conversation details.
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        $conversation = $this->messageService->getConversation($uuid, $request->user());

        $participants = $conversation->participants->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'role' => $p->role,
        ]);

        return $this->successResponse([
            'uuid' => $conversation->uuid,
            'type' => $conversation->type,
            'status' => $conversation->status,
            'ticket_id' => $conversation->ticket_id,
            'journey_id' => $conversation->journey_id,
            'participants' => $participants,
            'created_at' => $conversation->created_at?->toIso8601String(),
        ], 'Conversation details retrieved successfully.');
    }

    /**
     * Get paginated messages in conversation.
     */
    public function messages(Request $request, string $uuid): JsonResponse
    {
        $conversation = $this->messageService->getConversation($uuid, $request->user());
        $messages = $this->messageService->getConversationMessages(
            $conversation,
            $request->user(),
            (int) $request->query('per_page', 30)
        );

        $formatted = collect($messages->items())->map(function ($m) {
            $attachments = $m->attachments->map(fn($att) => [
                'media_uuid' => $att->media?->uuid,
                'file_name' => $att->media?->original_name,
                'mime_type' => $att->media?->mime_type,
                'file_size' => $att->media?->size,
            ]);

            return [
                'id' => $m->id,
                'sender_id' => $m->sender_id,
                'sender_name' => $m->sender?->name ?? 'User',
                'message_type' => $m->message_type,
                'body' => $m->body,
                'read_at' => $m->read_at?->toIso8601String(),
                'sent_at' => $m->sent_at?->toIso8601String(),
                'attachments' => $attachments,
            ];
        });

        return $this->successResponse([
            'messages' => $formatted,
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ], 'Messages retrieved successfully.');
    }

    /**
     * Send message in conversation.
     */
    public function storeMessage(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate([
            'message_type' => 'nullable|string|in:text,image',
            'body' => 'nullable|string|max:2000',
            'media_id' => 'nullable|integer|exists:media,id',
        ]);

        $conversation = $this->messageService->getConversation($uuid, $request->user());

        try {
            $message = $this->messageService->sendMessage($conversation, $request->user(), $validated);

            $attachments = $message->attachments->map(fn($att) => [
                'media_uuid' => $att->media?->uuid,
                'file_name' => $att->media?->original_name,
                'mime_type' => $att->media?->mime_type,
            ]);

            return $this->successResponse([
                'id' => $message->id,
                'conversation_uuid' => $conversation->uuid,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender?->name ?? 'User',
                'message_type' => $message->message_type,
                'body' => $message->body,
                'sent_at' => $message->sent_at?->toIso8601String(),
                'attachments' => $attachments,
            ], 'Message sent successfully.', 201);
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Mark unread messages in conversation as read.
     */
    public function markRead(Request $request, string $uuid): JsonResponse
    {
        $conversation = $this->messageService->getConversation($uuid, $request->user());
        $markedCount = $this->messageService->markMessagesAsRead($conversation, $request->user());

        return $this->successResponse([
            'marked_count' => $markedCount,
        ], 'Messages marked as read.');
    }
}
