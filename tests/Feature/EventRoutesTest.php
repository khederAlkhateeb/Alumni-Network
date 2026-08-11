<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\University;
use App\Models\UniversityAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Class EventRoutesTest
 *
 * Feature tests for University Event management and registration.
 * Covers Role-Based Access Control (RBAC) for university administrators and regular users.
 */
class EventRoutesTest extends TestCase
{
    use RefreshDatabase;

    private University $university;
    private User $uniAdminUser;
    private User $regularUser;

    /**
     * Set up the test environment before each test.
     * Initializes roles, a university, an admin user, and a regular user.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the 'uni_admin' role exists for the API guard
        Role::findOrCreate('uni_admin', 'api');

        // Create a dummy university
        $this->university = University::factory()->create();

        // Create a university admin user and assign the role and relation
        $this->uniAdminUser = User::factory()->create(['is_active' => true]);
        $this->uniAdminUser->assignRole('uni_admin');
        UniversityAdmin::create([
            'user_id' => $this->uniAdminUser->id,
            'university_id' => $this->university->id,
        ]);

        // Create a regular user for general access testing
        $this->regularUser = User::factory()->create(['is_active' => true]);
    }

    /**
     * Ensure unauthenticated users cannot access event routes.
     */
    public function test_unauthenticated_user_cannot_access_events(): void
    {
        $event = Event::factory()->create(['university_id' => $this->university->id]);

        // Attempting to access endpoints without Sanctum authentication should return 401
        $this->getJson("/api/v1/universities/{$this->university->id}/events")->assertStatus(401);
        $this->getJson("/api/v1/universities/{$this->university->id}/events/{$event->id}")->assertStatus(401);
        $this->postJson("/api/v1/universities/{$this->university->id}/events/{$event->id}/register")->assertStatus(401);
        $this->deleteJson("/api/v1/universities/{$this->university->id}/events/{$event->id}/register")->assertStatus(401);
    }

    /**
     * Ensure authenticated regular users can list all events for a university.
     */
    public function test_authenticated_user_can_list_events(): void
    {
        Event::factory()->count(3)->create(['university_id' => $this->university->id]);

        Sanctum::actingAs($this->regularUser);

        $response = $this->getJson("/api/v1/universities/{$this->university->id}/events");

        // Assert response structure and successful status
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
                    ]
                ],
                'meta'
            ]);
    }

    /**
     * Ensure authenticated users can view a specific event's details.
     */
    public function test_authenticated_user_can_view_single_event(): void
    {
        $event = Event::factory()->create(['university_id' => $this->university->id]);

        Sanctum::actingAs($this->regularUser);

        $response = $this->getJson("/api/v1/universities/{$this->university->id}/events/{$event->id}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $event->id);
    }

    /**
     * Test the full cycle of registering for an event and then cancelling the registration.
     */
    public function test_authenticated_user_can_register_and_cancel_for_event(): void
    {
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
    }

    /**
     * Ensure university administrators can create new events.
     */
    public function test_uni_admin_can_create_event(): void
    {
        Sanctum::actingAs($this->uniAdminUser);

        $response = $this->postJson("/api/v1/universities/{$this->university->id}/events", [
            'title' => 'Alumni Homecoming',
            'type' => 'campus',
            'location' => 'Main Auditorium',
            'start_date' => now()->addDays(5)->toDateTimeString(),
            'end_date' => now()->addDays(5)->addHours(2)->toDateTimeString(),
            'capacity' => 150,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.title', 'Alumni Homecoming');
    }

    /**
     * Ensure regular users are forbidden (403) from creating events.
     */
    public function test_non_uni_admin_cannot_create_event(): void
    {
        Sanctum::actingAs($this->regularUser);

        $response = $this->postJson("/api/v1/universities/{$this->university->id}/events", [
            'title' => 'Alumni Homecoming',
            'type' => 'campus',
            'location' => 'Main Auditorium',
            'start_date' => now()->addDays(5)->toDateTimeString(),
            'end_date' => now()->addDays(5)->addHours(2)->toDateTimeString(),
            'capacity' => 150,
        ]);

        $response->assertStatus(403);
    }

    /**
     * Ensure university administrators can update an existing event.
     */
    public function test_uni_admin_can_update_event(): void
    {
        $event = Event::factory()->create(['university_id' => $this->university->id]);

        Sanctum::actingAs($this->uniAdminUser);

        $response = $this->putJson("/api/v1/universities/{$this->university->id}/events/{$event->id}", [
            'title' => 'Updated Event Title',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.title', 'Updated Event Title');
    }

    /**
     * Ensure university administrators can view the list of users registered for an event.
     */
    public function test_uni_admin_can_view_event_registrations(): void
    {
        $event = Event::factory()->create(['university_id' => $this->university->id]);
        EventRegistration::factory()->create([
            'event_id' => $event->id,
            'user_id' => $this->regularUser->id,
        ]);

        Sanctum::actingAs($this->uniAdminUser);

        $response = $this->getJson("/api/v1/universities/{$this->university->id}/events/{$event->id}/registrations");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');
    }

    /**
     * Ensure university administrators can record attendance for a registered user.
     */
    public function test_uni_admin_can_record_attendance(): void
    {
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
    }
}
