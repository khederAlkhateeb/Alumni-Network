<?php

namespace Tests\Feature;

use App\Enums\enConnectionStatus;
use App\Models\Connection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConnectionRoutesTest extends TestCase
{
    use RefreshDatabase;

    private const GUARD = 'api';

    private const CONNECTION_PERMISSIONS = [
        'view-connections',
        'send-connection-request',
        'accept-connection-request',
        'reject-connection-request',
        'remove-connection',
        'block-user',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::CONNECTION_PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, self::GUARD);
        }
    }

    private function actingAlumni(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(
            collect(self::CONNECTION_PERMISSIONS)
                ->map(fn (string $name) => Permission::findByName($name, self::GUARD))
                ->all()
        );
        Sanctum::actingAs($user);

        return $user;
    }

    private function makeConnection(
        User $requester,
        User $receiver,
        enConnectionStatus $status = enConnectionStatus::PENGING,
        array $extra = [],
    ): Connection {
        return Connection::create(array_merge([
            'requester_id' => $requester->id,
            'receiver_id' => $receiver->id,
            'status' => $status,
            'accepted_at' => $status === enConnectionStatus::ACCEPTED ? now() : null,
            'rejected_at' => $status === enConnectionStatus::REJECTED ? now() : null,
        ], $extra));
    }

    public function test_unauthenticated_user_cannot_list_connections(): void
    {
        $this->getJson('/api/v1/connections')->assertStatus(401);
    }

    public function test_authenticated_user_can_list_accepted_connections(): void
    {
        $user = $this->actingAlumni();
        $other = User::factory()->create();
        $outsider = User::factory()->create();

        $this->makeConnection($user, $other, enConnectionStatus::ACCEPTED);
        $this->makeConnection($user, $outsider, enConnectionStatus::PENGING);

        $response = $this->getJson('/api/v1/connections');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Connections retrieved successfully.')
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => ['id', 'status', 'receiver', 'created_at'],
                ],
            ]);
    }

    public function test_authenticated_user_can_list_pending_connections(): void
    {
        $user = $this->actingAlumni();
        $requester = User::factory()->create();
        $friend = User::factory()->create();

        $pending = $this->makeConnection($requester, $user, enConnectionStatus::PENGING);
        $this->makeConnection($user, $friend, enConnectionStatus::ACCEPTED);

        $response = $this->getJson('/api/v1/connections/pending');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Pending Connections retrieved successfully.')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pending->id)
            ->assertJsonPath('data.0.status', 'pending');
    }

    public function test_unauthenticated_user_cannot_send_connection_request(): void
    {
        $receiver = User::factory()->create();

        $this->postJson("/api/v1/connections/{$receiver->id}")->assertStatus(401);
    }

    public function test_user_without_permission_cannot_send_connection_request(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        Sanctum::actingAs($sender);

        $this->postJson("/api/v1/connections/{$receiver->id}")->assertStatus(403);
    }

    public function test_authenticated_user_can_send_connection_request(): void
    {
        $sender = $this->actingAlumni();
        $receiver = User::factory()->create();

        $response = $this->postJson("/api/v1/connections/{$receiver->id}");

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Connection request sent successfully.')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.receiver.id', $receiver->id);

        $this->assertDatabaseHas('connections', [
            'requester_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => 'pending',
        ]);
    }

    public function test_cannot_send_connection_request_to_self(): void
    {
        $user = $this->actingAlumni();

        $this->postJson("/api/v1/connections/{$user->id}")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['receiver_id']);
    }

    public function test_cannot_send_duplicate_pending_connection_request(): void
    {
        $sender = $this->actingAlumni();
        $receiver = User::factory()->create();

        $this->makeConnection($sender, $receiver, enConnectionStatus::PENGING);

        $this->postJson("/api/v1/connections/{$receiver->id}")
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_cannot_send_connection_request_when_already_accepted(): void
    {
        $sender = $this->actingAlumni();
        $receiver = User::factory()->create();

        $this->makeConnection($sender, $receiver, enConnectionStatus::ACCEPTED);

        $this->postJson("/api/v1/connections/{$receiver->id}")
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_cannot_send_connection_request_when_blocked(): void
    {
        $sender = $this->actingAlumni();
        $receiver = User::factory()->create();

        $this->makeConnection($receiver, $sender, enConnectionStatus::BLOCKED);

        $this->postJson("/api/v1/connections/{$receiver->id}")
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_cannot_send_connection_request_during_rejection_cooldown(): void
    {
        $sender = $this->actingAlumni();
        $receiver = User::factory()->create();

        $this->makeConnection($sender, $receiver, enConnectionStatus::REJECTED, [
            'rejected_at' => Carbon::now()->subDays(1),
        ]);

        $this->postJson("/api/v1/connections/{$receiver->id}")
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_receiver_can_accept_pending_connection(): void
    {
        $requester = User::factory()->create();
        $receiver = $this->actingAlumni();
        $connection = $this->makeConnection($requester, $receiver, enConnectionStatus::PENGING);

        $response = $this->postJson("/api/v1/connections/{$connection->id}/accept");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Connection request accepted successfully.')
            ->assertJsonPath('data.status', 'accepted');

        $this->assertDatabaseHas('connections', [
            'id' => $connection->id,
            'status' => 'accepted',
        ]);
    }

    public function test_requester_cannot_accept_own_connection_request(): void
    {
        $requester = $this->actingAlumni();
        $receiver = User::factory()->create();
        $connection = $this->makeConnection($requester, $receiver, enConnectionStatus::PENGING);

        $this->postJson("/api/v1/connections/{$connection->id}/accept")
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_receiver_can_reject_pending_connection(): void
    {
        $requester = User::factory()->create();
        $receiver = $this->actingAlumni();
        $connection = $this->makeConnection($requester, $receiver, enConnectionStatus::PENGING);

        $response = $this->postJson("/api/v1/connections/{$connection->id}/reject");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Connection request rejected successfully.')
            ->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('connections', [
            'id' => $connection->id,
            'status' => 'rejected',
        ]);
    }

    public function test_party_can_delete_accepted_connection(): void
    {
        $user = $this->actingAlumni();
        $other = User::factory()->create();
        $connection = $this->makeConnection($user, $other, enConnectionStatus::ACCEPTED);

        $response = $this->deleteJson("/api/v1/connections/{$connection->id}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Connection deleted successfully.');

        $this->assertDatabaseMissing('connections', [
            'id' => $connection->id,
        ]);
    }

    public function test_cannot_delete_pending_connection(): void
    {
        $user = $this->actingAlumni();
        $other = User::factory()->create();
        $connection = $this->makeConnection($user, $other, enConnectionStatus::PENGING);

        $this->deleteJson("/api/v1/connections/{$connection->id}")
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_outsider_cannot_delete_connection(): void
    {
        $requester = User::factory()->create();
        $receiver = User::factory()->create();
        $connection = $this->makeConnection($requester, $receiver, enConnectionStatus::ACCEPTED);

        $this->actingAlumni();

        $this->deleteJson("/api/v1/connections/{$connection->id}")->assertStatus(403);
    }

    public function test_party_can_block_accepted_connection(): void
    {
        $user = $this->actingAlumni();
        $other = User::factory()->create();
        $connection = $this->makeConnection($user, $other, enConnectionStatus::ACCEPTED);

        $response = $this->postJson("/api/v1/connections/{$connection->id}/block");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Connection blocked successfully.');

        $this->assertDatabaseHas('connections', [
            'id' => $connection->id,
            'status' => 'blocked',
        ]);
    }

    public function test_cannot_block_pending_connection(): void
    {
        $user = $this->actingAlumni();
        $other = User::factory()->create();
        $connection = $this->makeConnection($user, $other, enConnectionStatus::PENGING);

        $this->postJson("/api/v1/connections/{$connection->id}/block")
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_returns_404_for_nonexistent_connection(): void
    {
        $this->actingAlumni();

        $this->postJson('/api/v1/connections/99999/accept')->assertStatus(404);
        $this->deleteJson('/api/v1/connections/99999')->assertStatus(404);
    }
}
