<?php

namespace App\Builders;

use App\Enums\EventStatus;
use App\Enums\EventType;
use Illuminate\Database\Eloquent\Builder;

/**
 * Custom query builder for the Event model.
 *
 * @method static EventBuilder query()
 * @method static EventBuilder status(EventStatus $status)
 * @method static EventBuilder ofType(EventType $type)
 * @method static EventBuilder forUniversity(int $universityId)
 * @method static EventBuilder startingBetween(\DateTimeInterface|string $from, \DateTimeInterface|string $to)
 * @method static EventBuilder notFull()
 */
class EventBuilder extends Builder
{
    /**
     * Filter events with the given status.
     *
     * @param EventStatus $status
     * @return static
     */
    public function status(EventStatus $status): static
    {
        return $this->where('status', $status); // Laravel automatically handles Enum casting
    }

    /**
     * Filter events by their type (on_campus, online, hybrid).
     *
     * @param EventType $type
     * @return static
     */
    public function ofType(EventType $type): static
    {
        return $this->where('type', $type);
    }

    /**
     * Filter events belonging to a specific university.
     *
     * @param int $universityId
     * @return static
     */
    public function forUniversity(int $universityId): static
    {
        return $this->where('university_id', $universityId);
    }

    /**
     * Filter events whose start date falls within the given range.
     *
     * @param \DateTimeInterface|string $from
     * @param \DateTimeInterface|string $to
     * @return static
     */
    public function startingBetween(\DateTimeInterface|string $from, \DateTimeInterface|string $to): static
    {
        return $this->whereBetween('start_date', [$from, $to]);
    }

    /**
     * Filter events that have not yet reached their capacity.
     * Events with a null capacity are considered unlimited.
     *
     * @return static
     */
    public function notFull(): static
    {
        return $this->where(function (Builder $query) {
            $query->whereNull('capacity')
                ->orWhereColumn('capacity', '>', 'registrations_count');
        })->withCount('registrations');
    }
}
