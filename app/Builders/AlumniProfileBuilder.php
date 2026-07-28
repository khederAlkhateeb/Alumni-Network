<?php

namespace App\Builders;

use App\Enums\ProfileStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * @method static AlumniProfileBuilder query()
 * @method static AlumniProfileBuilder active()
 * @method static AlumniProfileBuilder openToMentor()
 * @method static AlumniProfileBuilder graduatedIn(int $year)
 * @method static AlumniProfileBuilder withSkills(array $skillIds)
 * @method static AlumniProfileBuilder sameUniversityAs(int $universityId)
 */
class AlumniProfileBuilder extends Builder
{

    public function active(): static
    {
        return $this->where('status', ProfileStatus::ACTIVE);
    }


    public function openToMentor(): static
    {
        return $this->where('is_open_to_mentor', true);
    }


    public function graduatedIn(int $year): static
    {
        return $this->where('graduation_year', $year);
    }



    public function withSkills(array $skillIds): static
    {
        return $this->whereHas('skills', function (Builder $query) use ($skillIds) {
            $query->whereIn('skills.id', $skillIds);
        });
    }


    public function sameUniversityAs(int $universityId): static
    {
        return $this->whereHas('major.faculty', function (Builder $query) use ($universityId) {
            $query->where('university_id', $universityId);
        });
    }
public function newEloquentBuilder($query): AlumniProfileBuilder
{
    return new AlumniProfileBuilder($query);
}

}
