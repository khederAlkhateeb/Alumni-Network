<?php

namespace App\Http\Controllers;

use App\Http\Requests\University\StoreUniversityRequest;
use App\Http\Requests\University\UpdateUniversityRequest;
use App\Models\University;
use App\V1\Actions\University\CreateUniversity;
use App\V1\Actions\University\DeleteUniversity;
use App\V1\Actions\University\GetUniversity;
use App\V1\Actions\University\ListUniversities;
use App\V1\Actions\University\UpdateUniversity;
use App\V1\Resources\UniversityResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UniversityController extends Controller
{
    public function __construct(
        private readonly CreateUniversity $createUniversity,
        private readonly UpdateUniversity $updateUniversity,
        private readonly DeleteUniversity $deleteUniversity,
        private readonly GetUniversity $getUniversity,
        private readonly ListUniversities $listUniversities,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $universities = $this->listUniversities->handle($request->only('per_page'));

        return $this->successResponse(
            data: UniversityResource::collection($universities),
            meta: [
                'current_page' => $universities->currentPage(),
                'last_page'    => $universities->lastPage(),
                'per_page'     => $universities->perPage(),
                'total'        => $universities->total(),
            ],
        );
    }

    public function store(StoreUniversityRequest $request): JsonResponse
    {
        $university = $this->createUniversity->handle($request->validated());

        return $this->successResponse(
            data: new UniversityResource($university),
            message: 'University created successfully.',
            code: 201,
        );
    }

    public function show(University $university): JsonResponse
    {
        $university = $this->getUniversity->handle($university);

        return $this->successResponse(
            data: new UniversityResource($university),
        );
    }

    public function update(UpdateUniversityRequest $request, University $university): JsonResponse
    {
        $university = $this->updateUniversity->handle($university, $request->validated());

        return $this->successResponse(
            data: new UniversityResource($university),
            message: 'University updated successfully.',
        );
    }

    public function destroy(University $university): JsonResponse
    {
        $this->deleteUniversity->handle($university);

        return $this->successResponse(
            message: 'University deleted successfully.',
        );
    }
}
