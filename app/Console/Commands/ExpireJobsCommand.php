<?php

namespace App\Console\Commands;

use App\Models\JobListing;
use Illuminate\Console\Command;

class ExpireJobsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark active job listings whose expiry date has passed as expired';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = JobListing::query()
            ->where('status', JobListing::STATUS_ACTIVE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => JobListing::STATUS_EXPIRED]);

        $this->info("Expired {$count} job listing(s).");

        return self::SUCCESS;
    }
}
