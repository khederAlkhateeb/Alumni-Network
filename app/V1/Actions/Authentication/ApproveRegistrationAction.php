<?php 
namespace App\V1\Actions\Authentication;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendApproveNotificationJob;

class ApproveRegistrationAction
{
    /**
     * Approve a user's registration (alumni or student).
     */
    public function handle(User $targetUser): User
    {
        return DB::transaction(function () use ($targetUser) {
            // update the profile status to 'active' and activate the user account
            $profile = $targetUser->alumniProfile ?? $targetUser->studentProfile;

            if ($profile) {
            // update the profile status to 'active'
            $profile->update([
                    'status' => 'active',
                ]);
            }

            // activate the user account
            $targetUser->update([
                'is_active' => true,
            ]);

            SendApproveNotificationJob::dispatch($targetUser);

            return $targetUser->refresh();
        });
    }
}