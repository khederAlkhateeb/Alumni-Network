<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UniversityAdmin\StoreUniversityAdminRequest;
use App\Http\Requests\UniversityAdmin\UpdateUniversityAdminRequest;
use App\Models\UniversityAdmin;
use App\V1\Actions\UniversityAdmin\CreateUniversityAdminAction;
use App\V1\Actions\UniversityAdmin\ListUniversityAdminsAction;
use App\V1\Actions\UniversityAdmin\ShowUniversityAdminAction;
use App\V1\Actions\UniversityAdmin\UpdateUniversityAdminAction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UniversityAdminController extends Controller
{
    /**
     * @param CreateUniversityAdminAction $createUniversityAdminAction
     * @param ShowUniversityAdminAction $showUniversityAdminAction
     * @param UpdateUniversityAdminAction $updateUniversityAdminAction
     * @param ListUniversityAdminsAction $listUniversityAdminsAction
     */
    public function __construct(
        private readonly CreateUniversityAdminAction $createUniversityAdminAction,
        private readonly ShowUniversityAdminAction $showUniversityAdminAction,
        private readonly UpdateUniversityAdminAction $updateUniversityAdminAction,
        private readonly ListUniversityAdminsAction $listUniversityAdminsAction
    ) {}

    /**
     * View University admin list
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', UniversityAdmin::class);

            $perPage = $request->integer('per_page', 15);

            $admins = $this->listUniversityAdminsAction->handle($perPage);

            return $this->successResponse(
                data: $admins,
                message: 'University admins retrieved successfully.',
                code: 200
            );
        } catch (AuthorizationException $e) {
            return $this->errorResponse(
                message: 'This action is unauthorized.',
                code: 403
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                message: $e->getMessage() ?: 'An error occurred while fetching university admins.',
                code: $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500
            );
        }
    }

    /**
     * Create a new university admin user.
     *
     * @param StoreUniversityAdminRequest $request
     * @return JsonResponse
     */
    public function store(StoreUniversityAdminRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', UniversityAdmin::class);

            $user = $this->createUniversityAdminAction->handle($request->validated());

            return $this->successResponse(
                data: $user,
                message: 'University admin created successfully.',
                code: 201
            );
        } catch (AuthorizationException $e) {
            return $this->errorResponse(
                message: 'This action is unauthorized.',
                code: 403
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                message: $e->getMessage() ?: 'An error occurred while creating the university admin.',
                code: $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500
            );
        }
    }

    /**
     * Show a university admin info 
     *
     * @param UniversityAdmin $universityAdmin
     * @return JsonResponse
     */
    public function show(UniversityAdmin $universityAdmin): JsonResponse
    {
        try {
            $this->authorize('view', $universityAdmin);

            $adminDetails = $this->showUniversityAdminAction->handle($universityAdmin);

            return $this->successResponse(
                data: $adminDetails,
                message: 'University admin details retrieved successfully.',
                code: 200
            );
        } catch (AuthorizationException $e) {
            return $this->errorResponse(
                message: 'This action is unauthorized.',
                code: 403
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                message: $e->getMessage() ?: 'An error occurred while fetching university admin details.',
                code: $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500
            );
        }
    }

    /**
     * Update university admin info
     *
     * @param UpdateUniversityAdminRequest $request
     * @param UniversityAdmin $universityAdmin
     * @return JsonResponse
     */
    public function update(UpdateUniversityAdminRequest $request, UniversityAdmin $universityAdmin): JsonResponse
    {
        try {
            $this->authorize('update', $universityAdmin);

            $user = $this->updateUniversityAdminAction->handle($universityAdmin, $request->validated());

            return $this->successResponse(
                data: $user,
                message: 'University admin updated successfully.',
                code: 200
            );
        } catch (AuthorizationException $e) {
            return $this->errorResponse(
                message: 'This action is unauthorized.',
                code: 403
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                message: $e->getMessage() ?: 'An error occurred while updating the university admin.',
                code: $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500
            );
        }
    }
}