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
     * @return JsonResponse HTTP 200 with the collection of attached skills resources.
     *
     * @throws ModelNotFoundException If the profile is missing.
     */
    public function store(StoreAlumniSkillRequest $request, StoreAlumniSkillsAction $action): JsonResponse
    {
        $profile = $this->getAuthenticatedAlumniProfile();

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
     * @return JsonResponse HTTP 200 on successful removal.
     *
     * @throws ModelNotFoundException If the specified skill or profile record does not exist.
     */
    public function destroy(int $skill, DestroyAlumniSkillAction $action): JsonResponse
    {
        $profile = $this->getAuthenticatedAlumniProfile();

        $action->execute($profile, $skill);

        return $this->successResponse(
            message: 'Skill removed successfully.',
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
