<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\React\CreateReactRequest;
use App\Models\Reaction;
use App\V1\Actions\React\CreateReactionAction;

class ReactController extends Controller
{
    public function __construct(
        private readonly CreateReactionAction $action,
    ) {}

    /**
     * Create or update a reaction (like/insightful/celebrate)
     * on a post or comment.
     *
     * POST /reactions
     *
     * @param CreateReactRequest $request Validated: type, reactable_type, reactable_id.
     */
    public function store(CreateReactRequest $request)
    {
        $this->authorize('create', Reaction::class);

        $reaction = $this->action->handle($request->user(), $request->validated());

        return $this->successResponse(
            data: $reaction,
            message: 'Reaction created successfully',
            code: 201,
        );
    }
}
