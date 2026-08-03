<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alumni\ListAlumniRequest;
use App\Http\Requests\Alumni\UpdateAlumniProfile;
use App\Http\Requests\Alumni\UploadAlumniPhotoRequest;
use App\Http\Resources\AlumniProfileListResource;
use App\Http\Resources\AlumniProfileResource;
use App\Models\AlumniProfile;
use App\V1\Actions\AlumniProfile\CompleteAlumniProfileAction;
use App\V1\Actions\AlumniProfile\DeleteAlumniPhotoAction;
use App\V1\Actions\AlumniProfile\ListAlumniNetworkAction;
use App\V1\Actions\AlumniProfile\ShowAlumniProfileAction;
use App\V1\Actions\AlumniProfile\ToggleMentorAvailabilityAction;
use App\V1\Actions\AlumniProfile\UpdateAlumniPhotoAction;
use App\V1\Actions\AlumniProfile\UpdateAlumniProfileAction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Class AlumniProfileController
 *
 * Manages alumni profile actions including network listings, profile retrieval,
 * updates, mentorship status toggling, and photo uploads.
 *
 * @package App\Http\Controllers\Api\V1
 */
class AlumniProfileController extends Controller
{
    /**
     * Display a paginated list of the alumni network with filters.
     *
     * @param ListAlumniRequest $request Validated request containing query filters and pagination.
     * @param ListAlumniNetworkAction $action Action class executing the filtered query.
     * @return JsonResponse Paginated alumni list with metadata.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not authorized to view network.
     */
    public function index(ListAlumniRequest $request, ListAlumniNetworkAction $action): JsonResponse
    {
        $this->authorize('viewAny', AlumniProfile::class);

        $alumni = $action->execute($request->validated());

        return $this->successResponse(
            data: AlumniProfileListResource::collection($alumni->items()),
            message: 'Alumni network retrieved successfully.',
            meta: [
                'current_page' => $alumni->currentPage(),
                'last_page'    => $alumni->lastPage(),
                'per_page'     => $alumni->perPage(),
                'total'        => $alumni->total(),
            ]
        );
    }

    /**
     * Display a specific alumni profile by its ID.
     *
     * @param int $alumni The ID of the target alumni profile.
     * @param ShowAlumniProfileAction $action Action class fetching the profile.
     * @return JsonResponse Alumni profile details or 404 response if not found.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException If user cannot view the profile.
     */
    public function show(int $alumni, ShowAlumniProfileAction $action): JsonResponse
    {
        try {
            $profile = $action->execute($alumni);
        } catch (ModelNotFoundException) {
            return $this->profileNotFoundResponse();
        }

        $this->authorize('view', $profile);

        return $this->successResponse(
            data: new AlumniProfileResource($profile),
            message: 'Alumni profile retrieved successfully.',
        );
    }

    /**
     * Display the profile associated with the currently authenticated user.
     *
     * @return JsonResponse Detailed user profile with eager loaded relations.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException If user cannot view the profile.
     */
    public function showMe(): JsonResponse
    {
        $profile = $this->getAuthenticatedAlumniProfile();

        if (! $profile) {
            return $this->profileNotFoundResponse();
        }

        $this->authorize('view', $profile);

        $profile->load([
            'user',
            'skills',
            'workExperiences',
            'photo',
        ]);

        return $this->successResponse(
            data: new AlumniProfileResource($profile),
            message: 'Your profile retrieved successfully.',
        );
    }

    /**
     * Update the authenticated user's alumni profile.
     *
     * @param UpdateAlumniProfile $request Validated profile updates payload.
     * @param UpdateAlumniProfileAction $action Action class executing the updates.
     * @return JsonResponse Updated alumni profile resource.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException If user cannot update the profile.
     */
    public function updateMe(
        UpdateAlumniProfile $request,
        UpdateAlumniProfileAction $action
    ): JsonResponse {
        $profile = $this->getAuthenticatedAlumniProfile();

        if (! $profile) {
            return $this->profileNotFoundResponse();
        }

        $this->authorize('update', $profile);

        $updatedProfile = $action->execute($profile, $request->validated());

        return $this->successResponse(
            data: new AlumniProfileResource($updatedProfile),
            message: 'Profile updated successfully.',
        );
    }

    /**
     * Complete missing profile fields during setup or onboarding steps.
     *
     * @param UpdateAlumniProfile $request Validated profile payload.
     * @param CompleteAlumniProfileAction $action Action executing profile completion logic.
     * @return JsonResponse Completed alumni profile resource.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException If user cannot update the profile.
     */
    public function completeProfile(
        UpdateAlumniProfile $request,
        CompleteAlumniProfileAction $action
    ): JsonResponse {
        $profile = $this->getAuthenticatedAlumniProfile();

        if (! $profile) {
            return $this->profileNotFoundResponse();
        }

        $this->authorize('update', $profile);

        $updatedProfile = $action->execute($profile, $request->validated());

        return $this->successResponse(
            data: new AlumniProfileResource($updatedProfile),
            message: 'Profile completed successfully.'
        );
    }

    /**
     * Toggle the mentorship availability status for the authenticated alumni profile.
     *
     * @param ToggleMentorAvailabilityAction $action Action class switching the mentorship flag.
     * @return JsonResponse Profile resource with dynamic success message.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException If user cannot toggle mentorship.
     */
    public function toggleMentor(ToggleMentorAvailabilityAction $action): JsonResponse
    {
        $profile = $this->getAuthenticatedAlumniProfile();

        if (! $profile) {
            return $this->profileNotFoundResponse();
        }

        $this->authorize('toggleMentor', $profile);

        $updatedProfile = $action->execute($profile);

        return $this->successResponse(
            data: null,
            message: $updatedProfile->is_open_to_mentor
                ? 'Mentorship availability enabled successfully.'
                : 'Mentorship availability disabled successfully.',
        );
    }




    /**
     * Retrieve the alumni profile associated with the currently authenticated user.
     *
     * @return AlumniProfile|null Profile instance if exists, otherwise null.
     */
    private function getAuthenticatedAlumniProfile(): ?AlumniProfile
    {
        return Auth::user()?->alumniProfile;
    }

    /**
     * Generate a standardized 404 error response when an alumni profile is missing.
     *
     * @return JsonResponse Structured error response.
     */
    private function profileNotFoundResponse(): JsonResponse
    {
        return $this->errorResponse(
            message: 'No alumni profile associated with your account.',
            code: 404,
        );
    }

}
