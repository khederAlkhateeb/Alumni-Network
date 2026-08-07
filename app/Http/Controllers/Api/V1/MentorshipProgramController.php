<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MentorshipProgram\StoreMentorshipProgramRequest;
use App\Http\Requests\MentorshipProgram\UpdateMentorshipProgramRequest;
use App\Models\MentorshipProgram;
use App\Models\University;
use App\V1\Actions\MentorshipProgram\ActivateMentorshipProgramAction;
use App\V1\Actions\MentorshipProgram\CloseMentorshipProgramAction;
use App\V1\Actions\MentorshipProgram\CreateMentorshipProgramAction;
use App\V1\Actions\MentorshipProgram\ListMentorshipProgramsAction;
use App\V1\Actions\MentorshipProgram\UpdateMentorshipProgramAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MentorshipProgramController extends Controller
{

    /**
     * GET /universities/{university}/mentorship-programs
     *
     * List mentorship programs scoped to the authenticated user's university.
     */
    public function index(University $university, Request $request, ListMentorshipProgramsAction $listPrograms): JsonResponse
    {
        // try {

            $programs = $listPrograms->handle(
                $university->id
            );

            return $this->successResponse($programs, 'Mentorship programs retrieved successfully.');
        // } catch (\Throwable $exception) {
        //     report($exception);
        //     return $this->error('Unable to retrieve mentorship programs.', 500, ['exception' => $exception->getMessage()]);
        // }
    }

    /**
     * POST /universities/{university}/mentorship-programs
     *
     * Create a new mentorship program inside the university.
     */
    public function store(StoreMentorshipProgramRequest $request, University $university, CreateMentorshipProgramAction $createProgram): JsonResponse
    {
        try {
            $program = $createProgram->handle($university->id, $request->validated());

            return $this->successResponse( $program, 'Mentorship program created successfully.', [], 201);
        } catch (\Throwable $exception) {
            report($exception);
            return $this->errorResponse('Unable to create mentorship program.', [], 500);
        }
    }

    /**
     * PUT /universities/{university}/mentorship-programs/{mentorshipProgram}
     *
     * Update an existing mentorship program.
     */
    public function update(
        UpdateMentorshipProgramRequest $request,
        University $university,
        MentorshipProgram $mentorshipProgram,
        UpdateMentorshipProgramAction $updateProgram
    ): JsonResponse
    {
        try {
            $updatedProgram = $updateProgram->handle($mentorshipProgram, $request->validated());

            return $this->successResponse( $updatedProgram, 'Mentorship program updated successfully.');
        } catch (\Throwable $exception) {
            report($exception);
            return $this->errorResponse('Unable to update mentorship program.', [], 500);
        }
    }

    /**
     * POST /universities/{university}/mentorship-programs/{mentorshipProgram}/activate
     *
     * Activate a mentorship program and make it available for requests.
     */
    public function activate(
        University $university,
        MentorshipProgram $mentorshipProgram,
        ActivateMentorshipProgramAction $activateProgram
    ): JsonResponse
    {
        try {
            $activatedProgram = $activateProgram->handle($mentorshipProgram);

            return $this->successResponse( $activatedProgram, 'Mentorship program activated successfully.');
        } catch (\Throwable $exception) {
            report($exception);
            return $this->errorResponse('Unable to activate mentorship program.', [], 500);
        }
    }

    /**
     * POST /universities/{university}/mentorship-programs/{mentorshipProgram}/close
     *
     * Close a mentorship program and prevent new mentorship requests.
     */
    public function close(
        University $university,
        MentorshipProgram $mentorshipProgram,
        CloseMentorshipProgramAction $closeProgram
    ): JsonResponse
    {
        try {
            $closedProgram = $closeProgram->handle($mentorshipProgram);

            return $this->successResponse($closedProgram, 'Mentorship program closed successfully.');
        } catch (\Throwable $exception) {
            report($exception);
            return $this->errorResponse('Unable to close mentorship program.', [], 500);
        }
    }
}
