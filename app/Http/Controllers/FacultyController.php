<?php

namespace App\Http\Controllers;

use App\Http\Requests\Faculty\StoreFacultyRequest;
use App\Http\Requests\Faculty\UpdateFacultyRequest;
use App\Models\Faculty;
use App\V1\Actions\Faculty\CreateFaculty;
use App\V1\Actions\Faculty\DeleteFaculty;
use App\V1\Actions\Faculty\ListFaculties;
use App\V1\Actions\Faculty\ShowFaculty;
use App\V1\Actions\Faculty\UpdateFaculty as UpdateFacultyAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller responsible for managing faculties.
 *
 * Each controller action delegates business logic to a dedicated
 * Action class and returns a uniform API response through the
 * shared Controller response helpers.
 *
 * @package App\Http\Controllers
 */
class FacultyController extends Controller
{

    public function __construct()
    {
        // $this->middleware('permission:view-faculties')->only(['index', 'show']);
        // $this->middleware('permission:manage-faculties')->only(['store', 'update', 'destroy']);
    }
    /**
     * Display a paginated list of faculties.
     *
     * @param  ListFaculties $listFaculties The action that retrieves faculty listings.
     * @return JsonResponse HTTP 200 with paginated faculty data.
     */
    public function index(Request $request, ListFaculties $listFaculties): JsonResponse
    {
        $filters = $request->only(['name', 'per_page']);

        $faculties = $listFaculties->handle($filters);
        return $this->successResponse(
            data: $faculties,
            message: 'Faculties retrieved successfully.',
        );
    }

    /**
     * Store a newly created faculty in storage.
     *
     * @param  StoreFacultyRequest $request The validated request data.
     * @param  CreateFaculty $createFaculty The action that creates a faculty.
     * @return JsonResponse HTTP 201 with the created faculty.
     */
    public function store(StoreFacultyRequest $request, CreateFaculty $createFaculty): JsonResponse
    {
        $faculty = $createFaculty->handle($request->validated());
        return $this->successResponse(
            data: $faculty,
            message: 'Faculty created successfully.',
            code: 201,
        );
    }

    /**
     * Display the specified faculty.
     *
     * @param  Faculty $faculty The faculty being requested.
     * @param  ShowFaculty $showFaculty The action that loads a single faculty.
     * @return JsonResponse HTTP 200 with the faculty details.
     */
    public function show(Request $request, Faculty $faculty, ShowFaculty $showFaculty): JsonResponse
    {

        $result = $showFaculty->handle($faculty);
        return $this->successResponse(
            data: $result,
            message: 'Faculty retrieved successfully.',
        );
    }

    /**
     * Update the specified faculty in storage.
     *
     * @param  UpdateFacultyRequest $request The validated update payload.
     * @param  Faculty $faculty The faculty being updated.
     * @param  UpdateFacultyAction $updateFaculty The action that performs the update.
     * @return JsonResponse HTTP 200 with the updated faculty.
     */
    public function update(UpdateFacultyRequest $request, Faculty $faculty, UpdateFacultyAction $updateFaculty): JsonResponse
    {
        $result = $updateFaculty->handle($faculty, $request->validated());
        return $this->successResponse(
            data: $result,
            message: 'Faculty updated successfully.',
        );
    }

    /**
     * Remove the specified faculty from storage.
     *
     * @param  Faculty $faculty The faculty being deleted.
     * @param  DeleteFaculty $deleteFaculty The action that deletes the faculty.
     * @return JsonResponse HTTP 200 with deletion confirmation.
     */
    public function destroy(Request $request, Faculty $faculty, DeleteFaculty $deleteFaculty): JsonResponse
    {

        $deleteFaculty->handle($faculty);
        return $this->successResponse(
            data: null,
            message: 'Faculty deleted successfully.',
        );
    }
}
