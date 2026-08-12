<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateStudentProfileRequest;
use App\Http\Resources\StudentProfileResource;
use App\Models\StudentProfile;
use App\V1\Actions\StudentProfile\ShowStudentProfileAction;
use App\V1\Actions\StudentProfile\UpdateStudentProfileAction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller responsible for managing student profile operations.
 *
 * Features:
 * - Retrieve authenticated student's profile.
 * - Retrieve any student profile by ID (scoped by university and authorized via policy).
 * - Update authenticated student's profile.
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
     * @param Request $request
     * @param ShowStudentProfileAction $action
     * @return JsonResponse
     */
    public function showMe(Request $request, ShowStudentProfileAction $action): JsonResponse
    {
        $profile = $this->getAuthenticatedStudentProfile($request);

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
     * Note: Route Model Binding ($student) automatically applies the UniversityScope!
     * If the student belongs to another university, Laravel throws 404 ModelNotFoundException automatically.
     *
     * @param StudentProfile $student
     * @param ShowStudentProfileAction $action
     * @return JsonResponse
     */
    public function show(StudentProfile $student, ShowStudentProfileAction $action): JsonResponse
    {
        $this->authorize('view', $student);

        $profile = $action->handle($student);

        return $this->successResponse(
            data: new StudentProfileResource($profile),
            message: 'Student profile retrieved successfully.',
        );
    }

    /**
     * Update the authenticated student's own profile.
     *
     * @param UpdateStudentProfileRequest $request
     * @param UpdateStudentProfileAction $action
     * @return JsonResponse
     */
    public function updateMe(UpdateStudentProfileRequest $request, UpdateStudentProfileAction $action): JsonResponse
    {
        $profile = $this->getAuthenticatedStudentProfile($request);

        $this->authorize('update', $profile);

        $updatedProfile = $action->handle($profile, $request->validated());

        return $this->successResponse(
            data: new StudentProfileResource($updatedProfile),
            message: 'Profile updated successfully.',
        );
    }

    /**
     * Retrieve the student profile associated with the authenticated user.
     *
     * @param Request $request
     * @return StudentProfile
     * @throws ModelNotFoundException
     */
    private function getAuthenticatedStudentProfile(Request $request): StudentProfile
    {
        $profile = $request->user()?->studentProfile;

        if (! $profile) {
            throw (new ModelNotFoundException)->setModel(
                StudentProfile::class
            );
        }

        return $profile;
    }
}
