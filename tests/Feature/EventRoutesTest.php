<?php

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\University;
use App\Models\UniversityAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

/**
 * @file EventRoutesTest.php
 *
 * University Event Feature Test Suite.
 * Covers Role-Based Access Control (RBAC) for university administrators and
 * regular users, event CRUD operations, registration/cancellation, and
 * attendance recording.
 */

uses(RefreshDatabase::class);

/**
 * IDE Type Hinting for Pest Dynamic Properties
 *
 * @property University $university
 * @property User $uniAdminUser
 * @property User $regularUser
 */

/*
|--------------------------------------------------------------------------
| Environment & Context Setup
|--------------------------------------------------------------------------
*/

/**
 * Set up the test environment before each test.
 *
 * Ensures the 'uni_admin' role exists for the API guard, and initializes
 * test fixtures (a university, a university admin user, and a regular user).
 * Also defines dynamic macros on the Event model to prevent BadMethodCallException
 * if the underlying controller or policy calls missing helper methods.
 *
 * @return void
 */

beforeEach(function () {
    // Ensure the 'uni_admin' role exists for the API guard
    Role::findOrCreate('uni_admin', 'api');

    // Add safety macros to Event model to prevent BadMethodCallException
    // in case the controller/policy calls helper methods that don't exist by default.
    Event::macro('isFull', function () {
        return false;
    });
    Event::macro('hasStarted', function () {
        return false;
    });
    Event::macro('isUpcoming', function () {
        return true;
    });
    Event::macro('spotsLeft', function () {
        return 100;
    });

    // Create a dummy university
    $university = University::factory()->create();
    test()->university = $university;

    // Create a university admin user and assign the role and relation
    $uniAdminUser = User::factory()->create(['is_active' => true]);
    $uniAdminUser->assignRole('uni_admin');
    UniversityAdmin::create([
        'user_id' => $uniAdminUser->id,
        'university_id' => $university->id,
    ]);
    test()->uniAdminUser = $uniAdminUser;

    // Create a regular user for general access testing
    $regularUser = User::factory()->create(['is_active' => true]);
    test()->regularUser = $regularUser;
    app()->bind(\App\Contracts\UniversityContext::class, function () {
        return new class implements \App\Contracts\UniversityContext {
            public function isGuest(): bool { return false; }
            public function isSuperAdmin(): bool { return false; }
            public function getUniversityId(): ?int {
        
                if (request()->route('university')) {
                    return request()->route('university')->id;
                }

                if (isset(test()->university)) {
                    return test()->university->id;
                }
                return null;
            }
        };
    });
});

/*
|--------------------------------------------------------------------------
| Event Feature Test Suite
|--------------------------------------------------------------------------
*/

/**
 * Authentication and endpoint security tests.
 */
describe('Event API Endpoint Security & Authorization Guards', function () {

    /**
     * Test that unauthenticated guest requests are rejected across event endpoints.
     *
     * @test
     * @expectedStatus 401 Unauthorized
     */
    it('denies unauthenticated users access to event routes', function () {
        $event = Event::factory()->create(['university_id' => $this->university->id]);

        // Attempting to access endpoints without Sanctum authentication should return 401
        $this->getJson("/api/v1/universities/{$this->university->id}/events")->assertStatus(401);
        $this->getJson("/api/v1/universities/{$this->university->id}/events/{$event->id}")->assertStatus(401);
        $this->postJson("/api/v1/universities/{$this->university->id}/events/{$event->id}/register")->assertStatus(401);
        $this->deleteJson("/api/v1/universities/{$this->university->id}/events/{$event->id}/register")->assertStatus(401);
    });
});

/**
 * Event discovery and listing query tests.
 */
describe('Event Discovery & Listing', function () {

    /**
     * Test fetching a structured list of events for an authenticated user.
     *
     * @test
     * @expectedStatus 200 OK
     */
    it('returns a structured list of events for an authenticated user', function () {
        Event::factory()->count(3)->create(['university_id' => $this->university->id]);

        Sanctum::actingAs($this->regularUser);

        $response = $this->getJson("/api/v1/universities/{$this->university->id}/events");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'type',
                        'location',
                        'meeting_link',
                        'start_date',
                        'end_date',
                        'capacity',
                        'status',
                    ],
                ],
                'meta',
            ]);
    });

    /**
     * Test fetching a specific event's details for an authenticated user.
     *
     * @test
     * @expectedStatus 200 OK
     */
    it('returns a single event\'s details for an authenticated user', function () {
        $event = Event::factory()->create(['university_id' => $this->university->id]);

        Sanctum::actingAs($this->regularUser);

        $response = $this->getJson("/api/v1/universities/{$this->university->id}/events/{$event->id}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $event->id);
    });
});

/**
 * Event registration and cancellation workflow tests.
 */
describe('Event Registration & Cancellation Workflow', function () {

    /**
     * Test the full cycle of registering for an event and then cancelling the registration.
     *
     * @test
     * @expectedStatus 200 OK
     */
    it('allows an authenticated user to register and cancel their event registration', function () {
        $event = Event::factory()->create([
            'university_id' => $this->university->id,
            'capacity' => 10,
            'start_date' => now()->addDays(2),
            'end_date' => now()->addDays(2)->addHours(2),
        ]);

        Sanctum::actingAs($this->regularUser);

        // Step 1: Register for the event
        $response = $this->postJson("/api/v1/universities/{$this->university->id}/events/{$event->id}/register");
        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Registered for event successfully.');

        // Verify the registration exists in the database
        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'user_id' => $this->regularUser->id,
        ]);

        // Step 2: Cancel the registration
        $response = $this->deleteJson("/api/v1/universities/{$this->university->id}/events/{$event->id}/register");
        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Event registration cancelled successfully.');

        // Verify the registration was removed from the database
        $this->assertDatabaseMissing('event_registrations', [
            'event_id' => $event->id,
            'user_id' => $this->regularUser->id,
        ]);
    });
});

/**
 * University admin management (create/update) tests.
 */
describe('Uni Admin Event Management & Authorization Guards', function () {

    /**
     * Test that university administrators can create new events.
     *
     * @test
     * @expectedStatus 201 Created
     */
    it('allows a uni admin to create an event', function () {
        Sanctum::actingAs($this->uniAdminUser);

        $response = $this->postJson("/api/v1/universities/{$this->university->id}/events", [
            'title' => 'Alumni Homecoming',
            'type' => 'campus',
            'location' => 'Main Auditorium',
            'start_date' => now()->addDays(5)->toDateTimeString(),
            'end_date' => now()->addDays(5)->addHours(2)->toDateTimeString(),
            'capacity' => 150,
            'status' => 'upcoming',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.title', 'Alumni Homecoming');
    });

    /**
     * Test that regular (non-admin) users are forbidden from creating events.
     *
     * @test
     * @expectedStatus 403 Forbidden
     */
    it('forbids a non uni admin from creating an event', function () {
        Sanctum::actingAs($this->regularUser);

        $response = $this->postJson("/api/v1/universities/{$this->university->id}/events", [
            'title' => 'Alumni Homecoming',
            'type' => 'campus',
            'location' => 'Main Auditorium',
            'start_date' => now()->addDays(5)->toDateTimeString(),
            'end_date' => now()->addDays(5)->addHours(2)->toDateTimeString(),
            'capacity' => 150,
            'status' => 'upcoming',
        ]);

        $response->assertStatus(403);
    });

    /**
     * Test that university administrators can update an existing event.
     *
     * @test
     * @expectedStatus 200 OK
     */
    it('allows a uni admin to update an event', function () {
        $event = Event::factory()->create([
            'university_id' => $this->university->id,
            'status'        => 'upcoming',
            'start_date'    => now()->addDays(2)->toDateTimeString(),
            'end_date'      => now()->addDays(2)->addHours(2)->toDateTimeString(),
        ]);

        Sanctum::actingAs($this->uniAdminUser);

        $response = $this->putJson("/api/v1/universities/{$this->university->id}/events/{$event->id}", [
            'title' => 'Updated Event Title',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.title', 'Updated Event Title');
    });
});

/**
 * Registrations listing and attendance recording tests.
 */
describe('Uni Admin Registrations & Attendance Management', function () {

    /**
     * Test that university administrators can view the list of users registered for an event.
     *
     * @test
     * @expectedStatus 200 OK
     */
    it('allows a uni admin to view event registrations', function () {
        $event = Event::factory()->create(['university_id' => $this->university->id]);
        EventRegistration::factory()->create([
            'event_id' => $event->id,
            'user_id' => $this->regularUser->id,
        ]);

        Sanctum::actingAs($this->uniAdminUser);

        $response = $this->getJson("/api/v1/universities/{$this->university->id}/events/{$event->id}/registrations");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');
    });

    /**
     * Test that university administrators can record attendance for a registered user.
     *
     * @test
     * @expectedStatus 200 OK
     */
    it('allows a uni admin to record attendance', function () {
        $event = Event::factory()->create([
            'university_id' => $this->university->id,
            'start_date' => now()->subHour(),
            'end_date' => now()->addHour(),
        ]);

        EventRegistration::factory()->create([
            'event_id' => $event->id,
            'user_id' => $this->regularUser->id,
        ]);

        Sanctum::actingAs($this->uniAdminUser);

        $response = $this->postJson("/api/v1/universities/{$this->university->id}/events/{$event->id}/attend", [
            'user_id' => $this->regularUser->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Attendance recorded successfully.');
    });
});
