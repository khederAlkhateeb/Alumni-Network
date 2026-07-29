<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\V1\Actions\Authentication\ApproveRegistrationAction;
use App\V1\Actions\Authentication\RejectRegistrationAction;
use App\Models\University;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class RegistrationManagementController extends Controller
{
    /**
     * @param ApproveRegistrationAction $approveRegistration Handles registration approval logic.
     * @param RejectRegistrationAction  $rejectRegistration  Handles registration rejection logic.
     */
    public function __construct(
        private readonly ApproveRegistrationAction $approveRegistration,
        private readonly RejectRegistrationAction $rejectRegistration,
    ) {
    }

    /**
     * Approve a user's registration for a specific university.
     * This action is restricted to university admins and requires the admin to be associated with the same university as the user being approved.
     * @param University $university The university for which the registration is being approved.
     * @param User       $user       The user whose registration is being approved.
     * @return JsonResponse A JSON response containing the approved user's data and a success message.
     */
    public function approveUser(University $university, User $user): JsonResponse
    {
        $this->authorize('approve', [$user, $university]);

        $approvedUser = $this->approveRegistration->handle($user);

        return $this->successResponse(
            data: $approvedUser,
            message: 'User registration approved successfully.',
            code: 200
        );
    }

    /**
     * Reject a user's registration for a specific university.
     * This action is restricted to university admins and requires the admin to be associated with the same university as the user being rejected.
     * @param University $university The university for which the registration is being rejected.
     * @param User       $user       The user whose registration is being rejected.
     * @return JsonResponse A JSON response containing the rejected user's data and a success message.
     */
    public function rejectUser(University $university, User $user): JsonResponse
    {
        $this->authorize('reject', [$user, $university]);

        $rejectedUser = $this->rejectRegistration->handle($user);

        return $this->successResponse(
            data: $rejectedUser,
            message: 'User registration rejected successfully.',
            code: 200
        );
    }
}