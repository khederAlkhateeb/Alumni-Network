<?php
namespace App\V1\Actions\Authentication;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class RejectRegistrationAction
{
    /**
     * Reject a user's registration.
     */
    public function handle(User $targetUser): User
    {
        return DB::transaction(function () use ($targetUser) {
            $profile = $targetUser->alumniProfile ?? $targetUser->studentProfile;

            if ($profile) {
                $profile->update([
                    'status' => 'suspended',
                ]);
            }

            $targetUser->update([
                'is_active' => false,
            ]);

            return $targetUser->refresh();
        });
    }
}