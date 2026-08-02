<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\ProfileStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * Custom Eloquent query builder for StudentProfile model.
 *
 * This builder provides expressive, reusable query scopes for filtering
 * student profiles based on status, major, graduation year, and university.
 *
 * Usage:
 * - StudentProfile::query()->active()->get();
 * - StudentProfile::query()->pending()->sameUniversityAs(3)->get();
 * - StudentProfile::query()->inMajor(12)->graduatingIn(2026)->get();
 *
 * Each method returns the builder instance, allowing fluent chaining.
 */
class StudentProfileBuilder extends Builder
{
    /**
     * Filter student profiles with ACTIVE status.
     *
     * @return static
     */
    public function active(): static
    {
        return $this->where('status', ProfileStatus::ACTIVE);
    }

    /**
     * Filter student profiles with PENDING status.
     *
     * @return static
     */
    public function pending(): static
    {
        return $this->where('status', ProfileStatus::PENDING);
    }

    /**
     * Filter student profiles with SUSPENDED status.
     *
     * @return static
     */
    public function suspended(): static
    {
        return $this->where('status', ProfileStatus::SUSPENDED);
    }

    /**
     * Filter student profiles belonging to a specific major.
     *
     * @param int $majorId The ID of the major.
     *
     * @return static
     */
    public function inMajor(int $majorId): static
    {
        return $this->where('major_id', $majorId);
    }

    /**
     * Filter student profiles by expected graduation year.
     *
     * @param int $year The graduation year.
     *
     * @return static
     */
    public function graduatingIn(int $year): static
    {
        return $this->where('expected_graduation_year', $year);
    }

    /**
     * Filter student profiles belonging to a specific university.
     *
     * Logic:
     * - A student belongs to a university through:
     *      student → major → faculty → university_id
     *
     * @param int $universityId The ID of the university.
     *
     * @return static
     */
    public function sameUniversityAs(int $universityId): static
    {
        return $this->whereHas('major.faculty', function (Builder $query) use ($universityId) {
            $query->where('university_id', $universityId);
        });
    }
}
