<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Message\SendMessageRequest;
use App\Models\Conversation;
use App\V1\Actions\Messages\GetConversationMessagesAction;
use App\V1\Actions\Messages\SendMessageAction;

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
     *
     * @param Conversation $conversation The conversation to list messages for.
     */
    public function show(Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $messages = $this->getConversationMessagesAction->handle($conversation);
        return $this->successResponse(
            data: $messages->items(),
            message: "Messages retrieved successfully.",
            meta: [
                'path'          => $messages->path(),
                'per_page'      => $messages->perPage(),
                'next_cursor'   => $messages->nextCursor()?->encode(),
                'prev_cursor'   => $messages->previousCursor()?->encode(),
                'next_page_url' => $messages->nextPageUrl(),
                'prev_page_url' => $messages->previousPageUrl(),
            ],
            code: 200,
        );
    }

    /**
     * Send a message to another user, creating the conversation
     * between them if it doesn't already exist.
     *
     * POST /messages
     *
     * @param SendMessageRequest $request Validated: receiver_id, content.
     */
    public function store(SendMessageRequest $request)
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
}
