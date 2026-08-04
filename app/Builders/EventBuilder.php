<?php

namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;

/**
 * Custom query builder for the Event model.
 *
 * Encapsulates reusable query constraints (status filters, capacity
 * checks, date-range filters, etc.) so they stay out of controllers
 * and actions, and remain chainable and testable.
 *
 * @method static EventBuilder query()
 * @method static EventBuilder upcoming()
 * @method static EventBuilder ongoing()
 * @method static EventBuilder completed()
 * @method static EventBuilder cancelled()
 * @method static EventBuilder status(string $status)
 * @method static EventBuilder ofType(string $type)
 * @method static EventBuilder forUniversity(int $universityId)
 * @method static EventBuilder startingBetween(\DateTimeInterface|string $from, \DateTimeInterface|string $to)
 * @method static EventBuilder notFull()
 */
class EventBuilder extends Builder
{
    /**
     * Filter events with the given status.
     *
     * @param  string  $status
     * @return static
     */
    public function status(string $status): static
    {
        return $this->where('status', $status);
    }

    /**
     * Filter only upcoming events.
     *
     * @return static
     */
    public function upcoming(): static
    {
        return $this->status('upcoming');
    }

    /**
     * Filter only ongoing (currently happening) events.
     *
     * @return static
     */
    public function ongoing(): static
    {
        return $this->status('ongoing');
    }

    /**
     * Filter only completed events.
     *
     * @return static
     */
    public function completed(): static
    {
        return $this->status('completed');
    }

    /**
     * Filter only cancelled events.
     *
     * @return static
     */
    public function cancelled(): static
    {
        return $this->status('cancelled');
    }

    /**
     * Filter events by their type (on_campus, online, hybrid).
     *
     * @param  string  $type
     * @return static
     */
    public function ofType(string $type): static
    {
        return $this->where('type', $type);
    }

    /**
     * Filter events belonging to a specific university.
     *
     * @param  int  $universityId
     * @return static
     */
    public function forUniversity(int $universityId): static
    {
        return $this->where('university_id', $universityId);
    }

    /**
     * Filter events whose start date falls within the given range.
     *
     * @param  \DateTimeInterface|string  $from
     * @param  \DateTimeInterface|string  $to
     * @return static
     */
    public function startingBetween(\DateTimeInterface|string $from, \DateTimeInterface|string $to): static
    {
        return $this->whereBetween('start_date', [$from, $to]);
    }

    /**
     * Filter events that have not yet reached their capacity.
     * Events with a null capacity are considered unlimited and always included.
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
