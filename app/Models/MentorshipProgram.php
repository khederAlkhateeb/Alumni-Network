<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'university_id',
    'title',
    'start_date',
    'end_date',
    'mentor_per_mentees_max',
    'status',
])]
#[Hidden(['created_at', 'updated_at'])]
class MentorshipProgram extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Cast attribute values for proper API serialization.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * Get the university that owns this mentorship program.
     *
     * @return BelongsTo
     */
    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }



    /**
     * Get the mentorship requests attached to this program.
     *
     * @return HasMany
     */
    public function requests(): HasMany
    {
        return $this->hasMany(MentorshipRequest::class, 'program_id');
    }
}
