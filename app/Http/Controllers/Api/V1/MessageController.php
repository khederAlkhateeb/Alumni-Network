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


/**
     * Display a paginated list of conversations for the authenticated user.
     *
     * @param Request $request The incoming HTTP request instance.
     * @param ListConversationsAction $action The action responsible for fetching conversations.
     * @return \Illuminate\Http\JsonResponse JSON response containing the conversation resource collection.
     */
    public function index(Request $request, ListConversationsAction $action)
    {
        $user = $request->user();

        $conversations = $action->handle($user);

        return $this->successResponse(
            data: ConversationResource::collection($conversations),
            message: 'Get Conversation successfully',
            code: 200,
        );
    }

    /**
     * Mark all unread messages from a specific sender as read.
     *
     * @param Request $request The incoming HTTP request instance.
     * @param mixed $uid The ID of the sender whose messages are being marked as read.
     * @param MarkMessagesAsReadAction $action The action responsible for updating message statuses and broadcasting events.
     * @return \Illuminate\Http\JsonResponse JSON response indicating success.
     */
    public function markAsRead(Request $request, $uid, MarkMessagesAsReadAction $action)
    {
        $action->handle($request->user()->id, (int)$uid);

        return $this->successResponse(
            data: null,
            message: 'Messages marked as read',
            code: 200,
        );
    } }


