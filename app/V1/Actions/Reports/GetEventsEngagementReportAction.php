<?php

namespace App\V1\Actions\Reports;

use App\Models\Event;
use App\Models\University;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class GetEventsEngagementReportAction
{
    private const CACHE_TTL = 1800;

    /**
     * Handle the request for the events engagement report.
     *
     * @param University $university
     * @return array
     */
    public function handle(University $university): array
    {
        try {
            return Cache::remember(
                "university_{$university->id}_report_events_engagement",
                self::CACHE_TTL,
                function () use ($university) {
                    $events = Event::query()
                        ->forUniversity($university->id)
                        ->withCount([
                            'registrations',
                            'registrations as attendances_count' => fn ($query) => $query
                                ->whereNotNull('attended_at'),
                        ])
                        ->get();

                    $totalRegistrations = $events->sum('registrations_count');
                    $totalAttendances = $events->sum('attendances_count');

                    return [
                        'total_events' => $events->count(),
                        'events_by_status' => $events->groupBy('status')
                            ->map(fn ($group) => $group->count())
                            ->all(),
                        'total_registrations' => $totalRegistrations,
                        'total_attendances' => $totalAttendances,
                        'attendance_rate' => $totalRegistrations > 0
                            ? round(($totalAttendances / $totalRegistrations) * 100, 1)
                            : 0.0,
                        'events' => $events->map(fn (Event $event) => [
                            'event_id' => $event->id,
                            'title' => $event->title,
                            'status' => $event->status,
                            'capacity' => $event->capacity,
                            'registrations' => $event->registrations_count,
                            'attendances' => $event->attendances_count,
                        ])->all(),
                    ];
                }
            );
        } catch (Throwable $exception) {
            Log::error('GetEventsEngagementReport failed', [
                'university_id' => $university->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            throw $exception;
        }
    }
}