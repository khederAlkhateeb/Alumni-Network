<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use ILLuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Builders\JobApplicationBuilder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;


#[Fillable([
    'job_listing_id',
    'applicant_id',
    'cover_letter',
    'resume',
    'status',

])]
class JobApplication extends Model
{
    use HasFactory;

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_SHORTLISTED = 'shortlisted';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_SUBMITTED,
        self::STATUS_REVIEWED,
        self::STATUS_SHORTLISTED,
        self::STATUS_REJECTED,
    ];

    public function jobListing(): BelongsTo
    {
        return $this->belongsTo(JobListing::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    public function newEloquentBuilder($query): JobApplicationBuilder
    {
        return new JobApplicationBuilder($query);
    }
}
