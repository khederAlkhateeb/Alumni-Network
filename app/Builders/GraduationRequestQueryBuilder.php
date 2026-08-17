<?php
namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;

/**
 * Custom Query Builder for GraduationRequest Model.
 *
 * @extends Builder<\App\Models\GraduationRequest>
 */
class GraduationRequestQueryBuilder extends Builder
{
    /**
     * Filter by request status (pending, approved, rejected).
     */
    public function whereStatus(?string $status): self
    {
        return $this->when(!empty($status), fn ($q) => $q->where('status', $status));
    }

    /**
     * Filter by student's major ID.
     */
    public function whereMajor(?int $majorId): self
    {
        return $this->when(!empty($majorId), function ($q) use ($majorId) {
            $q->whereHas('studentProfile', fn ($sq) => $sq->where('major_id', $majorId));
        });
    }

    /**
     * Filter by student name or student number search query.
     */
    public function search(?string $search): self
    {
        return $this->when(!empty($search), function ($q) use ($search) {
            $q->whereHas('studentProfile', function ($sq) use ($search) {
                $sq->where('student_number', 'like', "%{$search}%")
                   ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        });
    }

    /**
     * Filter graduation requests by university ID (Multi-Tenancy Isolation).
     */
    public function forUniversity(?int $universityId): self
    {
        return $this->when(!empty($universityId), function ($q) use ($universityId) {
            $q->whereHas('studentProfile', fn ($sq) => $sq->where('university_id', $universityId));
        });
    }

    /**
     * Apply all dynamic filters including tenant university context.
     *
     * @param array{status?: string, major_id?: int, search?: string, university_id?: int} $filters
     */
    public function filter(array $filters): self
    {
        return $this
            ->forUniversity($filters['university_id'] ?? null)
            ->whereStatus($filters['status'] ?? null)
            ->whereMajor($filters['major_id'] ?? null)
            ->search($filters['search'] ?? null);
    }
}
