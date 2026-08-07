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
   public function pending(): static
    {
        return $this->where('status', ProfileStatus::PENDING);
    }
    public function filters(array $filters): static
{
    return $this
        ->active()
        ->when($filters['university_id'] ?? null, fn($q) => $q->sameUniversityAs($filters['university_id']))
        ->when($filters['major_id'] ?? null, fn($q) => $q->where('major_id', $filters['major_id']))
        ->when($filters['graduation_year'] ?? null, fn($q) => $q->graduatedIn($filters['graduation_year']))
        ->when($filters['skill_id'] ?? null, fn($q) => $q->withSkills((array) $filters['skill_id']))
        ->when(filter_var($filters['is_open_to_mentor'] ?? false, FILTER_VALIDATE_BOOLEAN),
            fn($q) => $q->openToMentor()
        );
}

public function forUniversity(int $university): self
    {
        return $this->active()->sameUniversityAs($university);
    }


    public function byMajorSummary(): array
    {
        return $this->with('major:id,name')
            ->get()
            ->groupBy('major_id')
            ->map(fn ($profiles, $majorId) => [
                'major_id' => $majorId ?: null,
                'major_name' => optional($profiles->first()->major)->name ?? __('Unspecified'),
                'total' => $profiles->count(),
            ])
            ->values()
            ->all();
    }


    public function byGraduationYearSummary(): array
    {
        return $this->selectRaw('graduation_year, count(*) as total')
            ->groupBy('graduation_year')
            ->orderByDesc('graduation_year')
            ->get()
            ->map(fn ($row) => [
                'graduation_year' => $row->graduation_year,
                'total' => (int) $row->total,
            ])
            ->all();
    }

    public function employed(): self
    {
        return $this->whereHas('workExperiences', fn ($q) => $q->whereNull('end_date'));
    }


    public function topCompaniesSummary(int $limit = 5): array
    {
        return $this->whereNotNull('current_company')
            ->selectRaw('current_company, count(*) as total')
            ->groupBy('current_company')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'company' => $row->current_company,
                'total' => (int) $row->total,
            ])
            ->all();
    }

}
