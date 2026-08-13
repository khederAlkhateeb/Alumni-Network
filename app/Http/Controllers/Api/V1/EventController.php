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
use App\V1\Actions\Events\ListUniversityEvents;
use App\V1\Actions\Events\CreateEvent;
use App\V1\Actions\Events\UpdateEvent;
use App\V1\Actions\Events\RegisterForEvent;
use App\V1\Actions\Events\CancelEventRegistration;
use App\V1\Actions\Events\RecordEventAttendance;
use App\V1\Actions\Events\ListEventRegistrations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * Inject action classes used to handle event workflows.
     *
     * @param  ListUniversityEvents  $listUniversityEvents
     * @param  CreateEvent  $createEvent
     * @param  UpdateEvent  $updateEvent
     * @param  RegisterForEvent  $registerForEvent
     * @param  CancelEventRegistration  $cancelRegistration
     * @param  RecordEventAttendance  $recordAttendance
     * @param  ListEventRegistrations  $listEventRegistrations
     */
    public function __construct(
        private readonly ListUniversityEvents $listUniversityEvents,
        private readonly CreateEvent $createEvent,
        private readonly UpdateEvent $updateEvent,
        private readonly RegisterForEvent $registerForEvent,
        private readonly CancelEventRegistration $cancelRegistration,
        private readonly RecordEventAttendance $recordAttendance,
        private readonly ListEventRegistrations $listEventRegistrations,
    ) {}

    /**
     * GET /universities/{university}/events
     *
     * List all events belonging to the given university.
     *
     * @param  University  $university
     * @return JsonResponse
     */
    public function index(University $university): JsonResponse
    {
        $this->authorize('viewAny', [Event::class, $university]);

        $events = $this->listUniversityEvents->handle($university);

        return $this->successResponse(
            data: EventResource::collection($events)->resolve(),
            message: 'Events retrieved successfully.',
        );
    }

    /**
     * POST /universities/{university}/events
     *
     * Create a new event for the university.
     *
     * @param  StoreEventRequest  $request
     * @param  University  $university
     * @return JsonResponse
     */
    public function store(StoreEventRequest $request, University $university): JsonResponse
    {
        $event = $this->createEvent->handle($university, $request->validated());

        return $this->successResponse(
            data: new EventResource($event),
            message: 'Event created successfully.',
            code: 201,
        );
    }

    /**
     * PUT /universities/{university}/events/{event}
     *
     * Update an existing event.
     *
     * @param  UpdateEventRequest  $request
     * @param  University  $university
     * @param  Event  $event
     * @return JsonResponse
     */
    public function update(UpdateEventRequest $request, University $university, Event $event): JsonResponse
    {
        $updatedEvent = $this->updateEvent->handle($event, $request->validated());

        return $this->successResponse(
            data: new EventResource($updatedEvent),
            message: 'Event updated successfully.',
        );
    }

    /**
     * POST /universities/{university}/events/{event}/register
     *
     * Register the authenticated user for the given event.
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
     * Record attendance for a user at the given event.
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
     *
     * @param  University  $university
     * @param  Event  $event
     * @return JsonResponse
     */
    public function registrations(University $university, Event $event): JsonResponse
    {
        $this->authorize('manage', $event);

        $registrations = $this->listEventRegistrations->handle($event);

        return $this->successResponse(
            data: EventRegistrationResource::collection($registrations)->resolve(),
            message: 'Event registrations retrieved successfully.',
        );
    }
}
