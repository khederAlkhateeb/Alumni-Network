<?php

namespace App\Http\Controllers;

use App\Http\Requests\University\StoreUniversityRequest;
use App\Http\Requests\University\UpdateUniversityRequest;
use App\Models\University;
use App\V1\Actions\University\CreateUniversity;
use App\V1\Actions\University\GetUniversity;
use App\V1\Actions\University\ListUniversities;
use App\V1\Actions\University\UpdateUniversity;
use App\V1\Actions\University\DeleteUniversity;
use App\V1\Resources\UniversityResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UniversityController extends Controller
{
    /**
     * @param CreateUniversity $createUniversity Handles university creation logic.
     * @param UpdateUniversity $updateUniversity Handles university update logic.
     * @param GetUniversity    $getUniversity    Handles retrieving a single university.
     * @param ListUniversities $listUniversities Handles paginated university listing.
     * @param DeleteUniversity $deleteUniversity Handles university deletion logic.
     */
    public function __construct(
        private readonly CreateUniversity $createUniversity,
        private readonly UpdateUniversity $updateUniversity,
        private readonly GetUniversity $getUniversity,
        private readonly ListUniversities $listUniversities,
        private readonly DeleteUniversity $deleteUniversity,
    ) {
    }

    /**
     * List all universities (public).
     *
     * Returns a paginated list of all universities. This endpoint is
     * publicly accessible and does not require authentication.
     *
     * @return JsonResponse HTTP 200 with paginated university collection.
     */
    public function index(): JsonResponse
    {
        $universities = $this->listUniversities->handle(config('app.pagination.per_page'));

        return $this->successResponse(
            data: UniversityResource::collection($universities),
            meta: [
                'current_page' => $universities->currentPage(),
                'last_page' => $universities->lastPage(),
                'per_page' => $universities->perPage(),
                'total' => $universities->total(),
            ],
        );
    }

    /**
     * Create a new university.
     *
     * Requires the `super_admin` role. Validates the request payload
     * via StoreUniversityRequest and delegates creation to the
     * CreateUniversity action.
     *
     * @param  StoreUniversityRequest $request The validated creation payload.
     * @return JsonResponse HTTP 201 with the created university resource,
     *                      or 403 if the user is not authorized,
     *                      or 500 on server error.
     */
    public function store(StoreUniversityRequest $request): JsonResponse
    {
        try {
            Gate::authorize('create', University::class);
            $university = $this->createUniversity->handle($request->validated());

            return $this->successResponse(
                data: new UniversityResource($university),
                message: 'University created successfully.',
                code: 201,
            );

        } catch (AuthorizationException $e) {
            return $this->errorResponse('Access Denied , Forbidden User', [], 403);
        } catch (\Throwable $th) {
            return $this->errorResponse('server error', [], 500);
        }
    }

    /**
     * Show a specific university.
     *
     * Requires authentication. Any authenticated user can view
     * university details. Delegates to the GetUniversity action.
     *
     * @param  University $university The university model (route-model binding).
     * @return JsonResponse HTTP 200 with the university resource,
     *                      or 500 on server error.
     */
    public function show(University $university): JsonResponse
    {
        try {
            Gate::authorize('view', $university);
            $university = $this->getUniversity->handle($university);

            return $this->successResponse(
                data: new UniversityResource($university),
            );
        } catch (AuthorizationException $e) {
            return $this->errorResponse('Access Denied , Forbidden User', [], 403);
        } catch (\Throwable $th) {
            return $this->errorResponse('server error', [], 500);
        }
    }

    /**
     * Update an existing university.
     *
     * Requires the `uni_admin` role. Validates the request payload
     * via UpdateUniversityRequest and delegates the update to the
     * UpdateUniversity action.
     *
     * @param  UpdateUniversityRequest $request    The validated update payload.
     * @param  University              $university The university model (route-model binding).
     * @return JsonResponse HTTP 200 with the updated university resource,
     *                      or 403 if the user is not authorized,
     *                      or 500 on server error.
     */
    public function update(UpdateUniversityRequest $request, University $university): JsonResponse
    {
        try {
            Gate::authorize('update', $university);
            $university = $this->updateUniversity->handle($university, $request->validated());

            return $this->successResponse(
                data: new UniversityResource($university),
                message: 'University updated successfully.',
            );
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse('Record Not Found', [], 404);
        } catch (AuthorizationException $e) {
            return $this->errorResponse('Access Denied , Forbidden User', [], 403);
        } catch (\Throwable $th) {
            return $this->errorResponse('server error', [], 500);
        }
    }

    /**
     * Delete an existing university.
     *
     * Requires the `super_admin` role. Delegates the deletion to the DeleteUniversity action.
     *
     * @param  University $university The university model (route-model binding).
     * @return JsonResponse HTTP 200 on success,
     *                      or 403 if the user is not authorized,
     *                      or 500 on server error.
     */
    public function destroy(University $university): JsonResponse
    {
        try {
            Gate::authorize('delete', $university);
            $this->deleteUniversity->handle($university);

            return $this->successResponse(
                message: 'University deleted successfully.',
            );
        } catch (AuthorizationException $e) {
            return $this->errorResponse('Access Denied , Forbidden User', [], 403);
        } catch (\Throwable $th) {
            return $this->errorResponse('server error', [], 500);
        }
    }
}
