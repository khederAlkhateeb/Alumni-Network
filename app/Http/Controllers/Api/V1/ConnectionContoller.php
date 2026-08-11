<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\enConnectionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Connection\ConnectionRequest;
use App\Http\Resources\ConnectionResource;
use App\Models\Connection;
use App\Models\User;
use App\V1\Actions\Connection\AcceptConnectionAction;
use App\V1\Actions\Connection\BlockConnectionAction;
use App\V1\Actions\Connection\DeleteConnectionAction;
use App\V1\Actions\Connection\ListConnectionsAction;
use App\V1\Actions\Connection\RejectConnectionAction;
use App\V1\Actions\Connection\SendConnectionAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ConnectionContoller extends Controller
{

    public function __construct(
        private ListConnectionsAction $listConnections,
        private SendConnectionAction $sendConnectionAction,
        private AcceptConnectionAction $acceptConnectionAction,
        private RejectConnectionAction $rejectConnectionAction,
        private DeleteConnectionAction $deleteConnectionAction,
        private BlockConnectionAction $blockConnectionAction,
    ) {
    }

    /**
     * Display the list of all connections
     *
     * @return JsonResponse
     */
    public function index()
    {
        Gate::authorize('viewAny', Connection::class);
        $connections = $this->listConnections->handle();

        return $this->successResponse(
            data: ConnectionResource::collection($connections),
            message: 'Connections retrieved successfully.',
            code: 200
        );
    }

    /**
     * Display the list of pending connections
     *
     * @return JsonResponse
     */
    public function pending()
    {
        Gate::authorize('viewAny', Connection::class);
        $connections = $this->listConnections->handle(enConnectionStatus::PENDING->value);

        return $this->successResponse(
            data: ConnectionResource::collection($connections),
            message: 'Pending Connections retrieved successfully.',
            code: 200
        );
    }

    /**
     * Send a connection request to the given user.
     *
     * @param  User  $user  The user receiving the request.
     */
    public function store(ConnectionRequest $request, User $user): JsonResponse
    {
        Gate::authorize('create', Connection::class);

        $connection = $this->sendConnectionAction->handle($request->user(), $user);

        return $this->successResponse(
            data: new ConnectionResource($connection->load('receiver')),
            message: 'Connection request sent successfully.',
            code: 201
        );
    }

    /**
     * Accept a pending connection request.
     *
     * @param  Connection  $connection  The pending connection request to accept.
     * @return JsonResponse HTTP 200 with the updated connection.
     */
    public function accepte(Connection $connection): JsonResponse
    {
        Gate::authorize('accept', $connection);

        $connection = $this->acceptConnectionAction->handle(auth()->user(), $connection);

        return $this->successResponse(
            data: new ConnectionResource($connection->load('receiver')),
            message: 'Connection request accepted successfully.',
        );
    }

    /**
     * Reject a pending connection request.
     *
     * @param  Connection  $connection  The pending connection request to reject.
     * @return JsonResponse HTTP 200 with the updated connection.
     */
    public function reject(Connection $connection): JsonResponse
    {
        Gate::authorize('reject', $connection);

        $connection = $this->rejectConnectionAction->handle(auth()->user(), $connection);

        return $this->successResponse(
            data: new ConnectionResource($connection->load('receiver')),
            message: 'Connection request rejected successfully.',
        );
    }

    /**
     * Remove an accepted connection.
     *
     * @param  Connection  $connection  The connection to remove.
     * @return JsonResponse HTTP 200 on success.
     */
    public function destroy(Connection $connection): JsonResponse
    {
        Gate::authorize('delete', $connection);

        $this->deleteConnectionAction->handle(auth()->user(), $connection);

        return $this->successResponse(
            message: 'Connection deleted successfully.',
        );
    }

    public function block(Connection $connection): JsonResponse
    {
        Gate::authorize('block', $connection);

        $this->blockConnectionAction->handle(auth()->user(), $connection);

        return $this->successResponse(
            message: 'Connection blocked successfully.',
        );
    }
}
