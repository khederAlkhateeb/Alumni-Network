<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

/**
 * @file NotificationManagementTest.php
 *
 * Feature tests for user notifications workflow.
 * Covers listing notifications, marking a single notification as read,
 * marking all notifications as read, and API endpoint security.
 *
 * @property User $user
 */

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

/**
 * Helper function to create a fake database notification for a user.
 *
 * @param  User  $user    The user who will receive the notification.
 * @param  bool  $isRead  Whether the notification is already read (default: false).
 * @return string         The UUID/ID of the generated notification.
 */
function createFakeNotification(User $user, bool $isRead = false): string
{
    $notification = $user->notifications()->create([
        'type'         => 'App\Notifications\SystemAlertNotification',
        'related_type' => 'App\Models\User',
        'related_id'   => $user->id,
        'data'         => ['message' => 'This is a test notification.'],
        'read_at'      => $isRead ? now() : null,
    ]);

    // Return the REAL id that was saved in the database
    return (string) $notification->id;
}

/*
|--------------------------------------------------------------------------
| Environment & Context Setup
|--------------------------------------------------------------------------
*/

/**
 * Set up the test environment before each test.
 *
 * Initializes the default user for the testing context.
 */
beforeEach(function () {
    $this->user = User::factory()->create();
});

/*
|--------------------------------------------------------------------------
| Notification Feature Test Suite
|--------------------------------------------------------------------------
*/

/**
 * Authentication and endpoint security tests.
 */
describe('Notification API Endpoint Security', function () {

    /**
     * Test that unauthenticated guest requests are rejected across notification endpoints.
     *
     * @test
     * @expectedStatus 401 Unauthorized
     */
    it('denies unauthenticated users access to notification routes', function () {
        // Attempt to access list of notifications
        $this->getJson('/api/v1/notifications')->assertStatus(401);

        // Attempt to mark all as read
        $this->postJson('/api/v1/notifications/read-all')->assertStatus(401);

        // Attempt to mark a specific random notification as read
        $this->patchJson('/api/v1/notifications/' . Str::uuid()->toString() . '/read')->assertStatus(401);
    });
});

/**
 * Notification listing and management tests.
 */
describe('Notification Retrieval & Management', function () {

    /**
     * Test that an authenticated user can retrieve their notifications with pagination.
     *
     * @test
     * @expectedStatus 200 OK
     */
    it('allows a user to retrieve paginated notifications', function () {
        // Create 2 fake notifications for the user
        createFakeNotification($this->user);
        createFakeNotification($this->user);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Notifications retrieved successfully.')
            ->assertJsonStructure([
                'status',
                'message',
                'data',
                'meta',
            ]);

        // Verify that exactly 2 notifications were returned in the data array
        $this->assertCount(2, $response->json('data'));
    });

    /**
     * Test that an authenticated user can mark a specific notification as read.
     *
     * @test
     * @expectedStatus 200 OK
     */
    it('allows a user to mark a single notification as read', function () {
        $notificationId = createFakeNotification($this->user);

        Sanctum::actingAs($this->user);

        $response = $this->patchJson("/api/v1/notifications/{$notificationId}/read");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Notification marked as read.',
            ]);

        // Verify the notification's 'read_at' timestamp was updated in the database
        $this->assertNotNull($this->user->notifications()->find($notificationId)->read_at);
    });

    /**
     * Test that users are restricted from tampering with notifications belonging to others.
     *
     * @test
     * @expectedStatus 404 Not Found
     */
    it('forbids a user from marking another users notification as read', function () {
        $otherUser = User::factory()->create();
        $otherUserNotificationId = createFakeNotification($otherUser);

        Sanctum::actingAs($this->user);

        $response = $this->patchJson("/api/v1/notifications/{$otherUserNotificationId}/read");

        // Should return 404 since the notification doesn't belong to the authenticated user
        $response->assertStatus(404)
            ->assertJsonPath('message', 'The requested API endpoint was not found.');
    });

    /**
     * Test that a user can mark all of their unread notifications as read in a single request.
     *
     * @test
     * @expectedStatus 200 OK
     */
    it('allows a user to mark all unread notifications as read at once', function () {
        // Create 3 unread notifications
        createFakeNotification($this->user);
        createFakeNotification($this->user);
        createFakeNotification($this->user);

        Sanctum::actingAs($this->user);

        // Pre-assertion: Ensure there are 3 unread notifications
        $this->assertEquals(3, $this->user->unreadNotifications()->count());

        $response = $this->postJson('/api/v1/notifications/read-all');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'All notifications marked as read.',
                'data' => [
                    'marked_count' => 3
                ]
            ]);

        // Post-assertion: Ensure there are no unread notifications left
        $this->assertEquals(0, $this->user->unreadNotifications()->count());
    });
});
