<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkExperience\CreateWorkExperience;
use App\Http\Requests\WorkExperience\UpdateWorkExperience;
use App\Http\Resources\WorkExperienceResource;
use App\Models\AlumniProfile;
use App\V1\Actions\AlumniProfile\WorkExperience\DestroyWorkExperienceAction;
use App\V1\Actions\AlumniProfile\WorkExperience\StoreWorkExperienceAction;
use App\V1\Actions\AlumniProfile\WorkExperience\UpdateWorkExperienceAction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Handles work experience management HTTP requests for authenticated alumni.
 *
 * Provides endpoints to store, update, and delete work experiences associated
 * with the authenticated user's alumni profile, delegating business logic
 * to dedicated action classes.
 *
 * @package App\Http\Controllers\Api\V1
 */
class WorkExperienceController extends Controller
{
    /**
     * Add a new work experience to the authenticated alumni's profile.
     *
     * Validates the input payload, ensures the user has an associated alumni
     * profile, and creates a new work experience record.
     *
     * @param  CreateWorkExperience        $request The validated work experience payload.
     * @param  StoreWorkExperienceAction   $action  Handles the creation logic for work experience.
     * @return JsonResponse HTTP 201 with the created work experience resource.
     *
     * @throws ModelNotFoundException If the alumni profile is missing.
     */
    public function store(CreateWorkExperience $request, StoreWorkExperienceAction $action): JsonResponse
    {
        $profile = $this->getAuthenticatedAlumniProfile();

        $workExperience = $action->execute($profile, $request->validated());

        return $this->successResponse(
            data: new WorkExperienceResource($workExperience),
            message: 'Work experience added successfully.',
            code: 201,
        );
    }

    /**
     * Update an existing work experience owned by the authenticated alumni.
     *
     * Resolves the work experience record by ID, validates the updated attributes,
     * and applies changes ensuring ownership integrity.
     *
     * @param  UpdateWorkExperience        $request        The validated update payload.
     * @param  int                         $workExperience The ID of the work experience to update.
     * @param  UpdateWorkExperienceAction  $action         Handles the update logic for work experience.
     * @return JsonResponse HTTP 200 with updated resource.
     *
     * @throws ModelNotFoundException If the work experience record or profile is not found.
     * @throws ValidationException    If the update data violates business domain validation rules.
     */
    public function update(
        UpdateWorkExperience $request,
        int $workExperience,
        UpdateWorkExperienceAction $action
    ): JsonResponse {
        $profile = $this->getAuthenticatedAlumniProfile();

        $updated = $action->execute($profile, $workExperience, $request->validated());

        return $this->successResponse(
            data: new WorkExperienceResource($updated),
            message: 'Work experience updated successfully.',
        );
    }

    /**
     * Delete a work experience owned by the authenticated alumni.
     *
     * Removes the specified work experience record after verifying ownership.
     *
     * @param  int                          $workExperience The ID of the work experience to delete.
     * @param  DestroyWorkExperienceAction  $action         Handles the deletion logic.
     * @return JsonResponse HTTP 200 on successful deletion.
     *
     * @throws ModelNotFoundException If the work experience record or profile is not found.
     */
    public function destroy(int $workExperience, DestroyWorkExperienceAction $action): JsonResponse
    {
        $profile = $this->getAuthenticatedAlumniProfile();

        $action->execute($profile, $workExperience);

        return $this->successResponse(
            message: 'Work experience deleted successfully.',
        );
    }

    /**
     * Get the authenticated user's alumni profile relation.
     *
     * @return AlumniProfile
     * @throws ModelNotFoundException If the authenticated user has no associated alumni profile.
     */
    private function getAuthenticatedAlumniProfile(): AlumniProfile
    {
        $profile = Auth::user()?->alumniProfile;

        if (! $profile) {
            throw (new ModelNotFoundException)->setModel(
                AlumniProfile::class
            );
        }

        return $profile;
    }
}
