<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Major\StoreMajorRequest;
use App\Http\Resources\MajorResource;
use App\Models\Faculty;
use App\Models\Major;
use App\Models\University;
use App\V1\Actions\Major\ListMajorAction;
use App\V1\Actions\Major\StoreMajorAction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MajorController extends Controller
{
    public function __construct(
        private readonly ListMajorAction $listMajorAction,
        private readonly StoreMajorAction $storeMajorAction
    ) {}

    /**
     * Display a listing of the majors for a faculty.
     * 
     * @param Request $request
     * @param University $university
     * @param Faculty $faculty
     * @return JsonResponse
     */
    public function index(Request $request, University $university, Faculty $faculty): JsonResponse
    {
        try {
            $this->authorize('viewAny', Major::class);

            $majors = $this->listMajorAction->handle($faculty);

            return $this->successResponse(
                data: MajorResource::collection($majors),
                message: 'Majors retrieved successfully.',
                code: 200
            );
        } catch (AuthorizationException $e) {
            return $this->errorResponse(
                message: 'This action is unauthorized.',
                code: 403
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                message: $e->getMessage() ?: 'An error occurred while fetching majors.',
                code: $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500
            );
        }
    }

    /**
     * Store a newly created major under a faculty.
     * 
     * @param StoreMajorRequest $request
     * @param University $university
     * @param Faculty $faculty
     * @return JsonResponse
     */
    public function store(StoreMajorRequest $request, University $university, Faculty $faculty): JsonResponse
    {
        try {
            $this->authorize('create', [Major::class, $university, $faculty]);

            $major = $this->storeMajorAction->handle($faculty, $request->validated());

            return $this->successResponse(
                data: new MajorResource($major),
                message: 'Major created successfully',
                code: 201
            );
        } catch (AuthorizationException $e) {
            return $this->errorResponse(
                message: 'This action is unauthorized.',
                code: 403
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                message: $e->getMessage() ?: 'An error occurred while creating the major.',
                code: $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500
            );
        }
    }
}