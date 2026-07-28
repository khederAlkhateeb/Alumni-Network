<?php

namespace App\Models;

use App\Enums\ProfileStatus;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
// #[UseEloquentBuilder(AlumniProfileBuilder::class)]

#[Fillable([

    'user_id',
    'major_id',
    'student_number',
    'graduation_year',
    'current_job_title',
    'current_company',
    'linkedin_url',
    'bio',
    'city',
    'country',
    'status',
    'is_open_to_mentor'
])]
#[Appends([
    'total_experience_years',
    'is_currently_employed',
    'completeness_score',
    'missing_fields',

])]
class AlumniProfile extends Model
{   //
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ProfileStatus::class,
            'is_open_to_mentor' => 'boolean',
            'graduation_year' => "integer"
        ];
    }


    /************************RelationShips ************************** */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    public function workExperiences(): HasMany
    {

        return $this->hasMany(WorkExperience::class)->orderByDesc('start_date');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'alumni_profile_skill');
    }
    public function photo(): MorphOne
    {
        return $this->morphOne(Attachment::class, 'attachable')
            ->latestOfMany();
    }


    /********************************* Accessors & Mutators************************************ */


    protected function totalExperienceYears(): Attribute
    {
        return Attribute::get(function () {
            $totalMonths = $this->workExperiences->sum(function (WorkExperience $exp) {
                $end = $exp->end_date ?? now();
                return $exp->start_date->diffInMonths($end);
            });

            return round($totalMonths / 12, 1);
        });
    }



    protected function isCurrentlyEmployed(): Attribute
    {
        return Attribute::get(
            fn() => $this->workExperiences->contains(fn(WorkExperience $exp) => is_null($exp->end_date))
        );
    }


    protected function currentWorkExperience(): Attribute
    {
        return Attribute::get(
            fn() => $this->workExperiences->firstWhere('end_date', null)
        );
    }


    // protected function completenessScore(): Attribute
    // {
    //     return Attribute::get(function () {
    //         $weights = config('profile_scoring.weights');
    //         $score = 0;

    //         $score += $this->bio ? $weights['bio'] : 0;
    //         $score += $this->linkedin_url ? $weights['linkedin_url'] : 0;
    //         $score += $this->city ? $weights['city'] : 0;
    //         $score += $this->country ? $weights['country'] : 0;
    //         $score += $this->current_job_title ? $weights['current_job_title'] : 0;
    //         $score += $this->current_company ? $weights['current_company'] : 0;
    //         $score += $this->skills->isNotEmpty() ? $weights['has_at_least_one_skill'] : 0;
    //         $score += $this->workExperiences->isNotEmpty() ? $weights['has_at_least_one_work_experience'] : 0;

    //         return min($score, 100);
    //     });
    // }

    protected function completenessScore(): Attribute
    {
        return Attribute::get(function () {
            $weights = config('profile_scoring.weights', []);

            return collect($this->completionMap())
                ->filter()
                ->sum(fn($_, $field) => $weights[$field] ?? 0);
        });
    }

    /**
     * Accessor for missing fields list.
     */
    protected function missingFields(): Attribute
    {
        return Attribute::get(function () {
            $weights = config('profile_scoring.weights', []);

            return collect($this->completionMap())
                ->reject()
                ->map(fn($_, $field) => [
                    'field' => $field,
                    'points' => $weights[$field] ?? 0,
                ])
                ->values()
                ->all();
        });
    }


    private function completionMap(): array
    {
        return [
            'bio' => !empty($this->bio),
            'linkedin_url' => !empty($this->linkedin_url),
            'city' => !empty($this->city),
            'country' => !empty($this->country),
            'current_job_title' => !empty($this->current_job_title),
            'current_company' => !empty($this->current_company),
            'has_at_least_one_skill' => $this->skills->isNotEmpty(),
            'has_at_least_one_work_experience' => $this->workExperiences->isNotEmpty(),
        ];
    }

    public function newEloquentBuilder($query): \App\Builders\AlumniProfileBuilder
    {
        return new \App\Builders\AlumniProfileBuilder($query);
    }
}
