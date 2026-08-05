<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MentorshipRequest\StoreMentorshipRequest;
use App\Http\Requests\MentorshipRequest\UpdateStatusMentorshipRequest;
use App\Http\Resources\AvailableMentorResource;
use App\Http\Resources\MentorshipRequestResource;
use App\Http\Resources\UserResource;
use App\Models\MentorshipRequest;
use App\Models\User;
use App\V1\Actions\MentorshipRequest\CreateMentorshipRequestAction;
use App\V1\Actions\MentorshipRequest\GetAvailableMentorsAction;
use App\V1\Actions\MentorshipRequest\GetUserMentorshipRequestsAction;
use App\V1\Actions\MentorshipRequest\UpdateMentorshipRequestStatusAction;
use App\Enums\MentorshipRequestStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MentorshipRequestController extends Controller
{
    /**
     * GET /api/v1/mentors
     */
 public function availableMentors(Request $request, GetAvailableMentorsAction $action)
    {
        $mentors = $action->handle(
            $request->integer('page', 1),
            $request->integer('per_page')
        );
// dd($mentors );
        return $this->successResponse(

            data: AvailableMentorResource::collection($mentors),
            message: 'Available mentors retrieved successfully.',
            meta: [
                // 'current_page' => $mentors->currentPage(),
                // 'last_page'    => $mentors->lastPage(),
                // 'per_page'     => $mentors->perPage(),
                // 'total'        => $mentors->total(),
            ]
        );
    }

    /**
     * GET /api/v1/mentorship-requests/incoming
     */
    public function incoming(Request $request, GetUserMentorshipRequestsAction $action): JsonResponse
    {
        $requests = $action->handle(
            user: $request->user(),
            type: 'incoming',
            perPage: $request->integer('per_page')
        );
// dd($requests);
        return $this->successResponse(
            data: MentorshipRequestResource::collection($requests),
            message: 'Incoming mentorship requests retrieved successfully.',
            meta: [
                'current_page' => $requests->currentPage(),
                'last_page'    => $requests->lastPage(),
                'per_page'     => $requests->perPage(),
                'total'        => $requests->total(),
            ]
        );
    }

    /**
     * GET /api/v1/mentorship-requests/outgoing
     */
    public function outgoing(Request $request, GetUserMentorshipRequestsAction $action): JsonResponse
    {
        $requests = $action->handle(
            user: $request->user(),
            type: 'outgoing',
            perPage: $request->integer('per_page')
        );

        return $this->successResponse(
            data: MentorshipRequestResource::collection($requests),
            message: 'Outgoing mentorship requests retrieved successfully.',
            meta: [
                'current_page' => $requests->currentPage(),
                'last_page'    => $requests->lastPage(),
                'per_page'     => $requests->perPage(),
                'total'        => $requests->total(),
            ]
        );
    }

    /**
     * POST /api/v1/mentorship-requests
     */
    public function store(StoreMentorshipRequest $request, CreateMentorshipRequestAction $action): JsonResponse
    {
        $mentorshipRequest = $action->handle($request->user(), $request->validated());

        return $this->successResponse(
            data: new MentorshipRequestResource($mentorshipRequest->load(['mentor', 'program'])),
            message: 'Mentorship request submitted successfully.',
            code: 201
        );
    }

    /**
     * POST /api/v1/mentorship-requests/{mentorshipRequest}/accept
     */
    public function accept(MentorshipRequest $mentorshipRequest, UpdateMentorshipRequestStatusAction $action): JsonResponse
    {
        $this->authorize('updateStatus', $mentorshipRequest);

        $updated = $action->handle($mentorshipRequest, MentorshipRequestStatus::ACCEPTED);

        return $this->successResponse(
            data: new MentorshipRequestResource($updated->fresh(['mentor', 'mentee', 'program'])),
            message: 'Mentorship request accepted successfully.'
        );
    }

    /**
     * POST /api/v1/mentorship-requests/{mentorshipRequest}/reject
     */
    public function reject(MentorshipRequest $mentorshipRequest, UpdateMentorshipRequestStatusAction $action): JsonResponse
    {
        $this->authorize('updateStatus', $mentorshipRequest);

        $updated = $action->handle($mentorshipRequest, MentorshipRequestStatus::REJECTED);

        return $this->successResponse(
            data: new MentorshipRequestResource($updated->fresh(['mentor', 'mentee', 'program'])),
            message: 'Mentorship request rejected successfully.'
        );
    }

    /**
     * POST /api/v1/mentorship-requests/{mentorshipRequest}/complete
     */
    public function complete(MentorshipRequest $mentorshipRequest, UpdateMentorshipRequestStatusAction $action): JsonResponse
    {
        $this->authorize('updateStatus', $mentorshipRequest);

        $updated = $action->handle($mentorshipRequest, MentorshipRequestStatus::COMPLETED);

        return $this->successResponse(
            data: new MentorshipRequestResource($updated->fresh(['mentor', 'mentee', 'program'])),
            message: 'Mentorship session marked as completed successfully.'
        );
    }
    //تذكير ال errorhandling
}
