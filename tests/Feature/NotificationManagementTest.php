<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Class NotificationManagementTest
 *
 * Feature tests for user notifications including listing,
 * marking as read, and marking all as read.
 */
class NotificationManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Helper function to create a fake database notification for a user.
     */
    private function createFakeNotification(User $user, bool $isRead = false): string
    {
        // Let the database generate and assign the actual ID
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

    /**
     * Ensure unauthenticated users cannot access notification routes.
     */
    public function test_unauthenticated_user_cannot_access_notifications(): void
    {
        $this->getJson('/api/v1/notifications')->assertStatus(401);
        $this->postJson('/api/v1/notifications/read-all')->assertStatus(401);
        $this->patchJson('/api/v1/notifications/' . Str::uuid()->toString() . '/read')->assertStatus(401);
    }

    /**
     * Ensure a user can retrieve their paginated notifications.
     */
    public function test_user_can_retrieve_paginated_notifications(): void
    {
        $this->createFakeNotification($this->user);
        $this->createFakeNotification($this->user);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Notifications retrieved successfully.')
            ->assertJsonStructure([
                'status',
                'message',
                'data',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ]
            ]);

        $this->assertEquals(2, $response->json('meta.total'));
    }

    /**
     * Ensure a user can mark a single notification as read.
     */
    public function test_user_can_mark_single_notification_as_read(): void
    {
        $notificationId = $this->createFakeNotification($this->user);

        Sanctum::actingAs($this->user);

        $response = $this->patchJson("/api/v1/notifications/{$notificationId}/read");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Notification marked as read.',
            ]);

        // Verify the notification was updated in the database
        $this->assertNotNull($this->user->notifications()->find($notificationId)->read_at);
    }

    /**
     * Ensure a user gets a 404 error when trying to mark another user's notification as read.
     */
    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $otherUser = User::factory()->create();
        $otherUserNotificationId = $this->createFakeNotification($otherUser);

        Sanctum::actingAs($this->user);

        $response = $this->patchJson("/api/v1/notifications/{$otherUserNotificationId}/read");

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Notification not found.',
            ]);
    }

    /**
     * Ensure a user can mark all their unread notifications as read.
     */
    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $this->createFakeNotification($this->user);
        $this->createFakeNotification($this->user);
        $this->createFakeNotification($this->user);

        Sanctum::actingAs($this->user);

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

        $this->assertEquals(0, $this->user->unreadNotifications()->count());
    }
}
