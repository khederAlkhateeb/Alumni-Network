<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MentorshipRequest\StoreMentorshipRequest;
use App\Http\Resources\AvailableMentorResource;
use App\Http\Resources\MentorshipRequestResource;
use App\Models\MentorshipRequest;
use App\V1\Actions\MentorshipRequest\CreateMentorshipRequestAction;
use App\V1\Actions\MentorshipRequest\GetAvailableMentorsAction;
use App\V1\Actions\MentorshipRequest\GetUserMentorshipRequestsAction;
use App\V1\Actions\MentorshipRequest\UpdateMentorshipRequestStatusAction;
use App\Enums\MentorshipRequestStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class MentorshipRequestController
 *
 * Manages mentorship requests and mentor discovery including listing available mentors,
 * fetching incoming/outgoing requests, submitting requests, and updating request statuses.
 *
 * @package App\Http\Controllers\Api\V1
 */
class MentorshipRequestController extends Controller
{
    /**
     * Display a paginated list of available mentors open for mentorship.
     *
     * @param Request $request Incoming HTTP request containing optional 'per_page' query parameter.
     * @param GetAvailableMentorsAction $action Action class executing the cached mentor retrieval query.
     * @return JsonResponse Paginated collection of available mentors with automatic metadata.
     */
    public function availableMentors(Request $request, GetAvailableMentorsAction $action): JsonResponse
    {
        $mentors = $action->handle(
            perPage: $request->integer('per_page')
        );

        return $this->successResponse(
            data: AvailableMentorResource::collection($mentors),
            message: 'Available mentors retrieved successfully.'
        );
    }

    /**
     * Display a paginated list of incoming mentorship requests for the authenticated user.
     *
     * @param Request $request Incoming HTTP request containing the authenticated user.
     * @param GetUserMentorshipRequestsAction $action Action class fetching incoming mentorship requests.
     * @return JsonResponse Paginated collection of incoming mentorship requests.
     */
    public function incoming(Request $request, GetUserMentorshipRequestsAction $action): JsonResponse
    {
        $requests = $action->handle(
            user: $request->user(),
            type: 'incoming',
            perPage: $request->integer('per_page')
        );

        return $this->successResponse(
            data: MentorshipRequestResource::collection($requests),
            message: 'Incoming mentorship requests retrieved successfully.'
        );
    }

    /**
     * Display a paginated list of outgoing mentorship requests created by the authenticated user.
     *
     * @param Request $request Incoming HTTP request containing the authenticated user.
     * @param GetUserMentorshipRequestsAction $action Action class fetching outgoing mentorship requests.
     * @return JsonResponse Paginated collection of outgoing mentorship requests.
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
            message: 'Outgoing mentorship requests retrieved successfully.'
        );
    }

    /**
     * Submit a new mentorship request to a mentor for a specific active program.
     *
     * @param StoreMentorshipRequest $request Validated request containing program_id, mentor_id, and optional intro_message.
     * @param CreateMentorshipRequestAction $action Action class storing the new mentorship request.
     * @return JsonResponse Newly created mentorship request resource with loaded relations (HTTP 201).
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
     * Accept an incoming mentorship request.
     *
     * @param MentorshipRequest $mentorshipRequest The mentorship request resolved via Route Model Binding.
     * @param UpdateMentorshipRequestStatusAction $action Action class updating the request status to ACCEPTED.
     * @return JsonResponse Updated mentorship request resource with fresh relations.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not the target mentor.
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
     * Reject an incoming mentorship request.
     *
     * @param MentorshipRequest $mentorshipRequest The mentorship request resolved via Route Model Binding.
     * @param UpdateMentorshipRequestStatusAction $action Action class updating the request status to REJECTED.
     * @return JsonResponse Updated mentorship request resource with fresh relations.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not the target mentor.
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
     * Mark an active mentorship request session as completed.
     *
     * @param MentorshipRequest $mentorshipRequest The mentorship request resolved via Route Model Binding.
     * @param UpdateMentorshipRequestStatusAction $action Action class updating the request status to COMPLETED.
     * @return JsonResponse Updated mentorship request resource with fresh relations.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not the target mentor.
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
}
