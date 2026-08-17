<?php
namespace App\V1\Actions\GraduationRequest;

use App\Enums\GraduationRequestStatus;
use App\Models\GraduationRequest;
use App\Models\AlumniProfile;
use App\Enums\ProfileStatus;
use App\Jobs\SendGraduationApprovedNotificationJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ApproveGraduationRequestAction
{
    /**
     * Approve the graduation request and transition student to alumni.
     *
     * @throws ValidationException
     */
    public function handle(GraduationRequest $graduationRequest): AlumniProfile
    {

        if ($graduationRequest->status !== GraduationRequestStatus::PENDING) {
            throw ValidationException::withMessages([
                'graduation_request' => __('Only pending requests can be approved.'),
            ]);
        }

        return DB::transaction(function () use ($graduationRequest) {
            $studentProfile = $graduationRequest->studentProfile;

            $graduationRequest->update([
                'status'      =>  GraduationRequestStatus::APPROVED,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            $user = $studentProfile->user;
            $user->syncRoles(['alumni']);

            $studentProfile->update([
                'status' => ProfileStatus::SUSPENDED,
            ]);

            $alumniProfile = AlumniProfile::create([
                'user_id'         => $studentProfile->user_id,
                'major_id'        => $studentProfile->major_id,
                'student_number'  => $studentProfile->enrollment_number ?? $studentProfile->student_number,
                'graduation_year' => $studentProfile->expected_graduation_year,
                'status'          => ProfileStatus::INCOMPLETE,
            ]);

            SendGraduationApprovedNotificationJob::dispatch($alumniProfile);

            return $alumniProfile;
        });
    }
}
