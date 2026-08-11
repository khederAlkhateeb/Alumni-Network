<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Message\SendMessageRequest;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\V1\Actions\Messages\GetConversationMessagesAction;
use App\V1\Actions\Messages\ListConversationsAction;
use App\V1\Actions\Messages\MarkMessagesAsReadAction;
use App\V1\Actions\Messages\SendMessageAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(
        private readonly SendMessageAction $sendMessageAction,
        private readonly GetConversationMessagesAction $getConversationMessagesAction,
    ) {}

    /**
     * List paginated messages within a conversation.
     *
     * GET /conversations/{conversation}/messages
     */
   public function show(Conversation $conversation): JsonResponse
{
    $this->authorize('view', $conversation);

    $messages = $this->getConversationMessagesAction->handle($conversation);

    return $this->successResponse(
        data: $messages,
        message: "Messages retrieved successfully."
    );
}

    /**
     * Send a message to another user, creating the conversation
     * between them if it doesn't already exist.
     *
     * POST /messages
     */
    public function store(SendMessageRequest $request): JsonResponse
    {
        $data = $request->validated();

        $message = $this->sendMessageAction->handle(
            $request->user(),
            $data['receiver_id'],
            $data['content']
        );

        return $this->successResponse(
            data: $message,
            message: 'Message sent successfully',
            code: 201,
        );
    }

    /**
     * Display a paginated list of conversations for the authenticated user.
     */
    public function index(Request $request, ListConversationsAction $action): JsonResponse
    {
        $conversations = $action->handle($request->user());

        return $this->successResponse(
            data: ConversationResource::collection($conversations),
            message: 'Get Conversation successfully',
            code: 200,
        );
    }

    /**
     * Mark all unread messages from a specific sender as read.
     */
    public function markAsRead(Request $request, mixed $uid, MarkMessagesAsReadAction $action): JsonResponse
    {
        $action->handle($request->user()->id, (int) $uid);

        return $this->successResponse(
            data: null,
            message: 'Messages marked as read',
            code: 200,
        );
    }
}
