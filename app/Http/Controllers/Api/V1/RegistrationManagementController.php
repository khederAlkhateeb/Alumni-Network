<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AlumniProfileResource;
use App\Http\Resources\StudentProfileResource;
use App\Models\University;
use App\Models\User;
use App\V1\Actions\Authentication\ApproveRegistrationAction;
use App\V1\Actions\Authentication\GetPendingRegistrationsAction;
use App\V1\Actions\Authentication\RejectRegistrationAction;
use Illuminate\Auth\Access\AuthorizationException;
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
     * 
     * @param University $university The university for which the registration is being approved.
     * @param User       $user       The user whose registration is being approved.
     * @return JsonResponse
     */
    public function approveUser(University $university, User $user): JsonResponse
    {
        try {
            $this->authorize('approve', [$user, $university]);

            $approvedUser = $this->approveRegistration->handle($user);

            return $this->successResponse(
                data: $approvedUser,
                message: 'User registration approved successfully.',
                code: 200
            );
        } catch (AuthorizationException $e) {
            return $this->errorResponse(
                message: 'This action is unauthorized.',
                code: 403
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                message: $e->getMessage() ?: 'An error occurred while approving the user registration.',
                code: $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500
            );
        }
    }

    /**
     * Reject a user's registration for a specific university.
     * 
     * @param University $university The university for which the registration is being rejected.
     * @param User       $user       The user whose registration is being rejected.
     * @return JsonResponse
     */
    public function rejectUser(University $university, User $user): JsonResponse
    {
        try {
            $this->authorize('reject', [$user, $university]);

            $rejectedUser = $this->rejectRegistration->handle($user);

            return $this->successResponse(
                data: $rejectedUser,
                message: 'User registration rejected successfully.',
                code: 200
            );
        } catch (AuthorizationException $e) {
            return $this->errorResponse(
                message: 'This action is unauthorized.',
                code: 403
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                message: $e->getMessage() ?: 'An error occurred while rejecting the user registration.',
                code: $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500
            );
        }
    }

    /**
     * List pending alumni and student registrations for a university.
     *
     * @param  University  $university
     * @param  GetPendingRegistrationsAction  $action
     * @return JsonResponse
     */
    public function pending(University $university, GetPendingRegistrationsAction $action): JsonResponse
    {
        try {
            $this->authorize('viewPendingRegistrations', $university);

            $result = $action->handle($university);

            return $this->successResponse(
                data: [
                    'alumni' => AlumniProfileResource::collection($result['alumni']),
                    'students' => StudentProfileResource::collection($result['students']),
                ],
                message: 'Pending registrations retrieved successfully.',
                code: 200
            );
        } catch (AuthorizationException $e) {
            return $this->errorResponse(
                message: 'This action is unauthorized.',
                code: 403
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                message: $e->getMessage() ?: 'An error occurred while fetching pending registrations.',
                code: $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500
            );
        }
    }
}