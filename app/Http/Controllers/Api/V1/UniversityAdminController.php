<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\V1\Actions\UniversityAdmin\CreateUniversityAdminAction;
use App\V1\Actions\UniversityAdmin\ShowUniversityAdminAction;
use App\Models\UniversityAdmin;
use App\Http\Requests\UniversityAdmin\StoreUniversityAdminRequest;
use App\V1\Actions\UniversityAdmin\UpdateUniversityAdminAction;
use App\Http\Requests\UniversityAdmin\UpdateUniversityAdminRequest;
use App\V1\Actions\UniversityAdmin\ListUniversityAdminsAction;


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
        )
    {}
    /**
     * View University admin list
     *
     * @param Request $request
     * @return void
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', UniversityAdmin::class);

        $perPage = $request->integer('per_page', 15);

        $admins = $this->listUniversityAdminsAction->handle($perPage);

        return $this->successResponse(
            data: $admins,
            message: 'University admins retrieved successfully.',
            code: 200
        );
    }


    /**
     * create a new university admin user.
     *
     * @param StoreUniversityAdminRequest $request
     * @return void
     */
    public function store(StoreUniversityAdminRequest $request)
    {
        $this->authorize('create', UniversityAdmin::class);

        $user = $this->createUniversityAdminAction->handle($request->validated());

        return $this->successResponse(
            data: $user,
            message: 'University admin created successfully.',
            code: 201
        );
    }

    /**
     * Show a university admin info 
     *
     * @param UniversityAdmin $universityAdmin
     * @return void
     */
    public function show(UniversityAdmin $universityAdmin)
    {
        $this->authorize('view', $universityAdmin);

        $adminDetails = $this->showUniversityAdminAction->handle($universityAdmin);

        return $this->successResponse(
            data: $adminDetails,
            message: 'University admin details retrieved successfully.',
            code: 200
        );
    }

    /**
     * Update university admin info
     *
     * @param UpdateUniversityAdminRequest $request
     * @param UniversityAdmin $universityAdmin
     * @return void
     */
    public function update(UpdateUniversityAdminRequest $request, UniversityAdmin $universityAdmin)
    {
        $this->authorize('update', $universityAdmin);

        $user = $this->updateUniversityAdminAction->handle($universityAdmin, $request->validated());

        return $this->successResponse(
            data: $user,
            message: 'University admin updated successfully.',
            code: 200
        );
    }
}
