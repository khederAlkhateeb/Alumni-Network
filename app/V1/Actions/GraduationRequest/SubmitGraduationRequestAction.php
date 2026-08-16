<?php
namespace App\V1\Actions\GraduationRequest;

use App\Enums\GraduationRequestStatus;
use App\Models\StudentProfile;
use App\Models\GraduationRequest;
use App\Jobs\SendGraduationRequestNotificationJob;
use App\Services\UploadFileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Action for submitting a student graduation request.
 */
class SubmitGraduationRequestAction
{
    /**
     * Create a new action instance.
     *
     * @param UploadFileService $uploadFileService
     */
    public function __construct(
        private readonly UploadFileService $uploadFileService
    ) {}

    /**
     * Submit a graduation request for a student profile.
     *
     * @param StudentProfile $studentProfile
     * @param UploadedFile $file
     * @return GraduationRequest
     * @throws ValidationException
     */
    public function handle(StudentProfile $studentProfile, UploadedFile $file): GraduationRequest
    {
        //  Check if there is already a pending request
        $hasPendingRequest = $studentProfile->graduationRequests()
            ->where('status', GraduationRequestStatus::PENDING)
            ->exists();

        if ($hasPendingRequest) {
            throw ValidationException::withMessages([
                'graduation_request' => __('You already have a pending graduation request under review.'),
            ]);
        }

        return DB::transaction(function () use ($studentProfile, $file) {
            //  Upload the file
            $uploadResult = $this->uploadFileService->upload(
                $file,
                (string) $studentProfile->user_id
            );

            //  Create the record in the graduation requests table
            $graduationRequest = $studentProfile->graduationRequests()->create([
                'user_id'          => $studentProfile->user_id,
                'certificate_path' => $uploadResult['safe_filename'],
                'status'           => GraduationRequestStatus::PENDING,
            ]);

            //  Dispatch the notification job
            SendGraduationRequestNotificationJob::dispatch($studentProfile);

            return $graduationRequest;
        });
    }
}
