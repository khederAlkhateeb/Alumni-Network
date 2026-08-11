<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\University;
use App\Http\Resources\Api\V1\EventResource;
use App\Http\Resources\Api\V1\EventRegistrationResource;
use App\Http\Requests\Api\V1\Events\StoreEventRequest;
use App\Http\Requests\Api\V1\Events\UpdateEventRequest;
use App\Http\Requests\Api\V1\Events\RecordAttendanceRequest;
use App\V1\Actions\Events\RegisterForEvent;
use App\V1\Actions\Events\CancelEventRegistration;
use App\V1\Actions\Events\RecordEventAttendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Event Controller (API v1)
|--------------------------------------------------------------------------
|
| Handles CRUD operations for university events, along with user
| registration, cancellation, and attendance actions. Authorization
| for "store", "update", and "attend" is handled inside their
| respective Form Requests, so it is not duplicated here. Route model
| bindings for {event} are scoped to {university} (see routes file),
| so every $event here is guaranteed to belong to $university.
|
*/

class EventController extends Controller
{
    /**
     * Inject action classes used to handle event registration workflows.
     *
     * @param  RegisterForEvent  $registerForEvent
     * @param  CancelEventRegistration  $cancelRegistration
     * @param  RecordEventAttendance  $recordAttendance
     */
    public function __construct(
        private readonly RegisterForEvent $registerForEvent,
        private readonly CancelEventRegistration $cancelRegistration,
        private readonly RecordEventAttendance $recordAttendance,
    ) {}

    /**
     * GET /universities/{university}/events
     *
     * List all events belonging to the given university.
     * Rule 5.7: Pagination (20 items per page).
     *
     * @param  University  $university
     * @return JsonResponse
     */
    public function index(University $university): JsonResponse
    {
        $this->authorize('viewAny', [Event::class, $university]);

        $events = $university->events()->latest('start_date')->paginate(20);

        return $this->successResponse(
            data: EventResource::collection($events)->resolve(),
            message: 'Events retrieved successfully.',
        );
    }

    /**
     * POST /universities/{university}/events
     *
     * Create a new event for the university. Authorization is handled
     * by StoreEventRequest::authorize().
     *
     * @param  StoreEventRequest  $request
     * @param  University  $university
     * @return JsonResponse
     */
    public function store(StoreEventRequest $request, University $university): JsonResponse
    {
        $event = $university->events()->create($request->validated());

        return $this->successResponse(
            data: new EventResource($event),
            message: 'Event created successfully.',
            code: 201,
        );
    }

    /**
     * GET /universities/{university}/events/{event}
     *
     * Show a single event's details, including its registrations count.
     *
     * @param  University  $university
     * @param  Event  $event
     * @return JsonResponse
     */
    public function show(University $university, Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        return $this->successResponse(
            data: new EventResource($event->loadCount('registrations')),
            message: 'Event retrieved successfully.',
        );
    }

    /**
     * PUT /universities/{university}/events/{event}
     *
     * Update an existing event. Authorization is handled by
     * UpdateEventRequest::authorize().
     *
     * @param  UpdateEventRequest  $request
     * @param  University  $university
     * @param  Event  $event
     * @return JsonResponse
     */
    public function update(UpdateEventRequest $request, University $university, Event $event): JsonResponse
    {
        $event->update($request->validated());

        return $this->successResponse(
            data: new EventResource($event),
            message: 'Event updated successfully.',
        );
    }

    /**
     * POST /universities/{university}/events/{event}/register
     *
     * Register the authenticated (active) user for the given event.
     *
     * @param  Request  $request
     * @param  University  $university
     * @param  Event  $event
     * @return JsonResponse
     */
    public function register(Request $request, University $university, Event $event): JsonResponse
    {
        $this->authorize('view', $event); // Must be an active user

        $this->registerForEvent->handle($event, $request->user());

        return $this->successResponse(message: 'Registered for event successfully.');
    }

    /**
     * DELETE /universities/{university}/events/{event}/register
     *
     * Cancel the authenticated user's registration for the given event.
     *
     * @param  Request  $request
     * @param  University  $university
     * @param  Event  $event
     * @return JsonResponse
     */
    public function cancelRegistration(Request $request, University $university, Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        $this->cancelRegistration->handle($event, $request->user());

        return $this->successResponse(message: 'Event registration cancelled successfully.');
    }

    /**
     * POST /universities/{university}/events/{event}/attend
     *
     * Record attendance for a user at the given event (Uni Admin only).
     * Authorization is handled by RecordAttendanceRequest::authorize().
     *
     * @param  RecordAttendanceRequest  $request
     * @param  University  $university
     * @param  Event  $event
     * @return JsonResponse
     */
    public function attend(RecordAttendanceRequest $request, University $university, Event $event): JsonResponse
    {
        $this->recordAttendance->handle($event, $request->validated());

        return $this->successResponse(message: 'Attendance recorded successfully.');
    }

    /**
     * GET /universities/{university}/events/{event}/registrations
     *
     * List all registrations for a specific event (Uni Admin only).
     * Rule 5.7: Pagination (20 items per page).
     *
     * @param  University  $university
     * @param  Event  $event
     * @return JsonResponse
     */
    public function registrations(University $university, Event $event): JsonResponse
    {
        $this->authorize('manage', $event);

        $registrations = $event->registrations()->with('user')->paginate(20);

        return $this->successResponse(
            data: EventRegistrationResource::collection($registrations)->resolve(),
            message: 'Event registrations retrieved successfully.',
        );
    }
}
