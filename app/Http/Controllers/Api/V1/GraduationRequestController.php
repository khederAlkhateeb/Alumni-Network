<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GraduationRequests\FilterGraduationRequestsRequest;
use App\Http\Requests\GraduationRequests\RejectGraduationRequestRequest;
use App\Http\Requests\GraduationRequests\SubmitGraduationRequest;
use App\Models\GraduationRequest;
use App\V1\Actions\GraduationRequest\ApproveGraduationRequestAction;
use App\V1\Actions\GraduationRequest\GetGraduationRequestsAction;
use App\V1\Actions\GraduationRequest\RejectGraduationRequestAction;
use App\V1\Actions\GraduationRequest\SubmitGraduationRequestAction;
use Illuminate\Http\JsonResponse;

/**
 * @group Graduation & Alumni Requests
 *
 * APIs for managing student graduation requests and processing them by administrators.
 */
class GraduationRequestController extends Controller
{
    /**
     * Display a paginated list of graduation requests with dynamic filtering.
     *
     * @param FilterGraduationRequestsRequest $request
     * @param GetGraduationRequestsAction $action
     * @return JsonResponse
     */
    public function index(
        FilterGraduationRequestsRequest $request,
        GetGraduationRequestsAction $action
    ): JsonResponse {
        $requests = $action->handle(
            $request->validated(),
            $request->integer('per_page', 15)
        );

        return $this->successResponse(
            data: $requests,
            message: 'Graduation requests retrieved successfully.',
            code: 200
        );
    }

    /**
     * Submit a new graduation request.
     *
     * @param SubmitGraduationRequest $request
     * @param SubmitGraduationRequestAction $action
     * @return JsonResponse
     */
    public function store(
        SubmitGraduationRequest $request,
        SubmitGraduationRequestAction $action
    ): JsonResponse {
        $studentProfile = $request->user()->studentProfile;

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $request->file('graduation_certificate');

        $graduationRequest = $action->handle(
            $studentProfile,
            $file
        );

        return $this->successResponse(
            data: $graduationRequest,
            message: 'Graduation request submitted successfully.',
            code: 201
        );
    }

    /**
     * Approve a graduation request and transition student to alumni.
     *
     * @param GraduationRequest $graduationRequest
     * @param ApproveGraduationRequestAction $action
     * @return JsonResponse
     */
    public function approve(
        GraduationRequest $graduationRequest,
        ApproveGraduationRequestAction $action
    ): JsonResponse {
        $this->authorize('approve', $graduationRequest);

        $alumniProfile = $action->handle($graduationRequest);

        return $this->successResponse(
            data: $alumniProfile,
            message: 'Graduation request approved successfully.',
            code: 200
        );
    }

    /**
     * Reject a graduation request with a specified reason.
     *
     * @param GraduationRequest $graduationRequest
     * @param RejectGraduationRequestRequest $request
     * @param RejectGraduationRequestAction $action
     * @return JsonResponse
     */
    public function reject(
        GraduationRequest $graduationRequest,
        RejectGraduationRequestRequest $request,
        RejectGraduationRequestAction $action
    ): JsonResponse {
        $this->authorize('reject', $graduationRequest);

        $updatedRequest = $action->handle(
            $graduationRequest,
            $request->validated('rejection_reason')
        );

        return $this->successResponse(
            data: $updatedRequest,
            message: 'Graduation request rejected successfully.',
            code: 200
        );
    }
}
