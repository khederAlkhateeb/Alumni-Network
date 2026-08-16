<?php
namespace App\Models;

use App\Builders\GraduationRequestQueryBuilder;

use App\Enums\GraduationRequestStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model representing a student's graduation request.
 */
#[Fillable([
    'user_id',
    'student_profile_id',
    'certificate_path',
    'status',
    'rejection_reason',
    'reviewed_by',
    'reviewed_at',
])]
class GraduationRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => GraduationRequestStatus::class,
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the student profile associated with the graduation request.
     *
     * @return BelongsTo
     */
    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    /**
     * Get the user who reviewed the graduation request.
     *
     * @return BelongsTo
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Create a new Eloquent Query Builder instance for the model.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return GraduationRequestQueryBuilder
     */
    public function newEloquentBuilder($query): GraduationRequestQueryBuilder
    {
        return new GraduationRequestQueryBuilder($query);
    }
}
