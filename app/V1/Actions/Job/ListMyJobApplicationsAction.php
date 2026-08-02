<?php

namespace App\V1\Actions\Job;

use App\Models\JobApplication;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class ListMyJobApplicationsAction
{
    /**
     * Fetch all job applications submitted by the given user.
     */
    public function handle(): Collection
    {
        return JobApplication::query()
            ->where('applicant_id', Auth::id())
            ->with(['jobListing', 'jobListing.postedBy'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
