<?php

namespace App\V1\Actions\GraduationRequest;

use App\Enums\GraduationRequestStatus;
use App\Jobs\SendGraduationRejectedNotificationJob;
use App\Models\GraduationRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectGraduationRequestAction
{
    /**
     * Reject the graduation request with a reason.
     *
     * @throws ValidationException
     */
    public function handle(GraduationRequest $graduationRequest, string $rejectionReason): GraduationRequest
    {
        if ($graduationRequest->status !== GraduationRequestStatus::PENDING) {
            throw ValidationException::withMessages([
                'graduation_request' => __('Only pending requests can be rejected.'),
            ]);
        }

        return DB::transaction(function () use ($graduationRequest, $rejectionReason) {
            $graduationRequest->update([
                'status'           =>   GraduationRequestStatus::REJECTED ,
                'rejection_reason' => $rejectionReason,
                'reviewed_by'      => Auth::id(),
                'reviewed_at'      => now(),
            ]);


            SendGraduationRejectedNotificationJob::dispatch(
                $graduationRequest->studentProfile,
                $rejectionReason
            );

            return $graduationRequest;
        });
    }
}
