<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateStudentProfileRequest;
use App\Http\Resources\StudentProfileResource;
use App\Models\StudentProfile;
use App\V1\Actions\StudentProfile\ShowStudentProfileAction;
use App\V1\Actions\StudentProfile\UpdateStudentProfileAction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller responsible for managing student profile operations.
 *
 * Features:
 * - Retrieve authenticated student's profile.
 * - Retrieve any student profile by ID (with authorization).
 * - Update authenticated student's profile.
 *
 * Authorization:
 * - Viewing and updating profiles is controlled via StudentProfilePolicy.
 *
 * Routes:
 * - GET  /api/v1/students/me
 * - GET  /api/v1/students/{student}
 * - PUT  /api/v1/students/me
 */
class StudentProfileController extends Controller
{
    /**
     * Display the authenticated student's own profile.
     *
     * Endpoint:
     * - GET /api/v1/students/me
     *
     * Behavior:
     * - Retrieves the student profile associated with the authenticated user.
     * - Returns 404 if the user has no student profile.
     * - Loads missing relations (major → faculty) to support policy checks.
     * - Authorizes the view operation via StudentProfilePolicy.
     * - Delegates business logic to ShowStudentProfileAction.
     *
     * @param Request $request The incoming HTTP request containing the authenticated user.
     * @param ShowStudentProfileAction $action The action responsible for preparing the profile data.
     *
     * @return JsonResponse JSON response containing the authenticated student's profile.
     */
    public function showMe(Request $request, ShowStudentProfileAction $action): JsonResponse
    {
        $profile = $request->user()->studentProfile;

        if (!$profile) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Student profile not found.',
            ], 404);
        }

        $profile->loadMissing('major.faculty');

        $this->authorize('view', $profile);

        $loadedProfile = $action->handle($profile);

        return $this->successResponse(
            data: new StudentProfileResource($loadedProfile),
            message: 'My Profile get successfully.',
        );
    }

    /**
     * Display a specific student profile by ID.
     *
     * Endpoint:
     * - GET /api/v1/students/{student}
     *
     * Behavior:
     * - Loads missing relations (major → faculty).
     * - Authorizes the view operation via StudentProfilePolicy.
     * - Delegates business logic to ShowStudentProfileAction.
     *
     * @param StudentProfile $student The student profile resolved via route model binding.
     * @param ShowStudentProfileAction $action The action responsible for preparing the profile data.
     *
     * @return JsonResponse JSON response containing the requested student profile.
     */
    public function show(StudentProfile $student, ShowStudentProfileAction $action): JsonResponse
    {
        $student->loadMissing('major.faculty');

        $this->authorize('view', $student);

        $profile = $action->handle($student);

        return $this->successResponse(
            data: new StudentProfileResource($profile),
            message: 'Profile show successfully.',
        );
    }

    /**
     * Update the authenticated student's own profile.
     *
     * Endpoint:
     * - PUT /api/v1/students/me
     *
     * Behavior:
     * - Validates input using UpdateStudentProfileRequest.
     * - Retrieves the authenticated user's student profile.
     * - Returns 404 if no profile exists.
     * - Loads missing relations (major → faculty) to support policy checks.
     * - Authorizes the update operation via StudentProfilePolicy.
     * - Delegates update logic to UpdateStudentProfileAction.
     *
     * @param UpdateStudentProfileRequest $request The validated request containing update data.
     * @param UpdateStudentProfileAction $action The action responsible for performing the update.
     *
     * @return JsonResponse JSON response containing the updated student profile.
     */
    public function updateMe(UpdateStudentProfileRequest $request, UpdateStudentProfileAction $action): JsonResponse
    {
        $profile = $request->user()->studentProfile;

        if (!$profile) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Student profile not found for the authenticated user.',
            ], 404);
        }

        $profile->loadMissing('major.faculty');

        $this->authorize('update', $profile);

        $updatedProfile = $action->handle($profile, $request->validated());

        return $this->successResponse(
            data: new StudentProfileResource($updatedProfile),
            message: 'Profile updated successfully.',
        );
    }
}
