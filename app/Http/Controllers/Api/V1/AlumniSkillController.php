<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Skills\StoreAlumniSkillRequest;
use App\Http\Resources\SkillResource;
use App\Models\AlumniProfile;
use App\V1\Actions\AlumniProfile\Skill\DestroyAlumniSkillAction;
use App\V1\Actions\AlumniProfile\Skill\StoreAlumniSkillsAction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Handles skill management HTTP requests for authenticated alumni.
 *
 * Provides endpoints to attach and detach skills to/from the authenticated
 *
 * @package App\Http\Controllers\Api\V1
 */
class AlumniSkillController extends Controller
{
    /**
     * Attach one or more skills to the authenticated alumni's profile.
     *
     * Validates the input skills payload, verifies that the authenticated user
     * has an active alumni profile, and links the skills to the profile.
     *
     * @param  StoreAlumniSkillRequest   $request The validated skills payload.
     * @param  StoreAlumniSkillsAction  $action  Handles the skill attachment logic.
     * @return JsonResponse HTTP 200 with the collection of attached skills resources, or HTTP 404 if profile is missing.
     */
    public function store(StoreAlumniSkillRequest $request, StoreAlumniSkillsAction $action): JsonResponse
    {
        $profile = $this->getAuthenticatedAlumniProfile();

        if (! $profile) {
            return $this->errorResponse(
                message: 'No alumni profile associated with your account.',
                code: 404,
            );
        }

        $updatedProfile = $action->execute($profile, $request->validated());

        return $this->successResponse(
            data: SkillResource::collection($updatedProfile->skills),
            message: 'Skill added successfully.',
        );
    }

    /**
     * Detach a skill from the authenticated alumni's profile.
     *
     * Removes the association between the specified skill ID and the
     * authenticated user's alumni profile.
     *
     * @param  int                        $skill  The ID of the skill to detach.
     * @param  DestroyAlumniSkillAction   $action Handles the skill removal logic.
     * @return JsonResponse HTTP 200 on successful removal, or HTTP 404 if profile or skill is not found.
     *
     * @throws ModelNotFoundException If the specified skill record does not exist or is not associated with the profile.
     */
    public function destroy(int $skill, DestroyAlumniSkillAction $action): JsonResponse
    {
        $profile = $this->getAuthenticatedAlumniProfile();

        if (! $profile) {
            return $this->errorResponse(
                message: 'No alumni profile associated with your account.',
                code: 404,
            );
        }

        $action->execute($profile, $skill);

        return $this->successResponse(
            message: 'Skill removed successfully.',
        );
    }

    /**
     * Get the authenticated user's alumni profile relation.
     *
     * @return AlumniProfile|null The associated alumni profile or null if unauthenticated/unassociated.
     */
    private function getAuthenticatedAlumniProfile(): ?AlumniProfile
    {
        return Auth::user()?->alumniProfile;
    }
}
