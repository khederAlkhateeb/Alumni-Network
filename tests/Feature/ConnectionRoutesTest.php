<?php

use App\Enums\enConnectionStatus;
use App\Models\Connection;
use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

// ---------------------------------------------------------------------------
// Shared setup
// ---------------------------------------------------------------------------

const CONNECTION_PERMISSIONS = [
    'view-connections',
    'send-connection-request',
    'accept-connection-request',
    'reject-connection-request',
    'remove-connection',
    'block-user',
];

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (CONNECTION_PERMISSIONS as $permission) {
        Permission::findOrCreate($permission, 'api');
    }
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Create and authenticate a user who holds every connection permission.
 * This represents a fully-privileged alumni user in the system.
 */
function actingAlumni(): User
{
    $user = User::factory()->create();
    $user->givePermissionTo(
        collect(CONNECTION_PERMISSIONS)
            ->map(fn(string $name) => Permission::findByName($name, 'api'))
            ->all()
    );
    Sanctum::actingAs($user);

    return $user;
}

/**
 * Persist a Connection record directly, bypassing the action layer.
 * Use $extra to override any default attribute (e.g. rejected_at for cooldown tests).
 */
function makeConnection(
    User $requester,
    User $receiver,
    enConnectionStatus $status = enConnectionStatus::PENDING,
    array $extra = [],
): Connection {
    return Connection::create([
        'requester_id' => $requester->id,
        'receiver_id' => $receiver->id,
        'status' => $status,
        'accepted_at' => $status === enConnectionStatus::ACCEPTED ? now() : null,
        'rejected_at' => $status === enConnectionStatus::REJECTED ? now() : null,
        ...$extra,
    ]);
}

// ===========================================================================
// GET /api/v1/connections  — list accepted connections
// ===========================================================================

/**
 * The connections list endpoint requires authentication.
 * An unauthenticated request must be rejected with 401.
 */
test('unauthenticated user cannot list connections', function () {
    $this->getJson('/api/v1/connections')->assertStatus(401);
});

/**
 * Listing connections returns only ACCEPTED connections for the
 * authenticated user.  PENDING connections must not appear in the result.
 */
test('authenticated user can list accepted connections', function () {
    $user = actingAlumni();
    $other = User::factory()->create();
    $outsider = User::factory()->create();

    makeConnection($user, $other, enConnectionStatus::ACCEPTED);
    makeConnection($user, $outsider, enConnectionStatus::PENDING);

    $this->getJson('/api/v1/connections')
        ->assertStatus(200)
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
});

// ===========================================================================
// GET /api/v1/connections/pending  — list pending connections
// ===========================================================================

/**
 * The pending connections endpoint returns only PENDING connections
 * where the authenticated user is the receiver.
 * Accepted connections for the same user must not appear.
 */
test('authenticated user can list pending connections', function () {
    $user = actingAlumni();
    $requester = User::factory()->create();
    $friend = User::factory()->create();

    $pending = makeConnection($requester, $user, enConnectionStatus::PENDING);
    makeConnection($user, $friend, enConnectionStatus::ACCEPTED);

    $this->getJson('/api/v1/connections/pending')
        ->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('message', 'Pending Connections retrieved successfully.')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $pending->id)
        ->assertJsonPath('data.0.status', 'pending');
});

// ===========================================================================
// POST /api/v1/connections/{user}  — send connection request
// ===========================================================================

/**
 * Sending a connection request requires authentication.
 * An unauthenticated request must be rejected with 401.
 */
test('unauthenticated user cannot send connection request', function () {
    $receiver = User::factory()->create();

    $this->postJson("/api/v1/connections/{$receiver->id}")->assertStatus(401);
});

/**
 * The send-connection-request permission is required.
 * A user without that permission must receive 403 Forbidden.
 */
test('user without permission cannot send connection request', function () {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();
    Sanctum::actingAs($sender);

    $this->postJson("/api/v1/connections/{$receiver->id}")->assertStatus(403);
});

/**
 * A user with the correct permission can send a connection request.
 * The response must be 201 with a pending status and the receiver's data,
 * and the connection must be persisted to the database.
 */
test('authenticated user can send connection request', function () {
    $sender = actingAlumni();
    $receiver = User::factory()->create();

    $this->postJson("/api/v1/connections/{$receiver->id}")
        ->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('message', 'Connection request sent successfully.')
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.receiver.id', $receiver->id);

    $this->assertDatabaseHas('connections', [
        'requester_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'status' => 'pending',
    ]);
});

/**
 * A user cannot send a connection request to themselves.
 * The request must fail with 422 and a receiver_id validation error.
 */
test('cannot send connection request to self', function () {
    $user = actingAlumni();

    $this->postJson("/api/v1/connections/{$user->id}")
        ->assertStatus(422)
        ->assertJsonValidationErrors(['receiver_id']);
});

/**
 * Sending a second PENDING request to the same user must be rejected with 422.
 * Duplicate pending connections are not allowed.
 */
test('cannot send duplicate pending connection request', function () {
    $sender = actingAlumni();
    $receiver = User::factory()->create();

    makeConnection($sender, $receiver, enConnectionStatus::PENDING);

    $this->postJson("/api/v1/connections/{$receiver->id}")->assertStatus(422);
});

/**
 * Sending a request to a user who is already an ACCEPTED connection
 * must be rejected with 422.
 */
test('cannot send connection request when already accepted', function () {
    $sender = actingAlumni();
    $receiver = User::factory()->create();

    makeConnection($sender, $receiver, enConnectionStatus::ACCEPTED);

    $this->postJson("/api/v1/connections/{$receiver->id}")->assertStatus(422);
});

/**
 * If the target user has BLOCKED the sender, the request must be
 * rejected with 422 to prevent bypassing the block.
 */
test('cannot send connection request when blocked', function () {
    $sender = actingAlumni();
    $receiver = User::factory()->create();

    makeConnection($receiver, $sender, enConnectionStatus::BLOCKED);

    $this->postJson("/api/v1/connections/{$receiver->id}")->assertStatus(422);
});

/**
 * After a rejection, a cooldown period prevents the sender from
 * immediately re-requesting.  During the cooldown the request
 * must be rejected with 422.
 */
test('cannot send connection request during rejection cooldown', function () {
    $sender = actingAlumni();
    $receiver = User::factory()->create();

    makeConnection($sender, $receiver, enConnectionStatus::REJECTED, [
        'rejected_at' => Carbon::now()->subDays(1),
    ]);

    $this->postJson("/api/v1/connections/{$receiver->id}")->assertStatus(422);
});

// ===========================================================================
// POST /api/v1/connections/{connection}/accept  — accept connection
// ===========================================================================

/**
 * The receiver of a pending connection request can accept it.
 * The response must show the updated status as "accepted", and the
 * database record must be updated accordingly.
 */
test('receiver can accept pending connection', function () {
    $requester = User::factory()->create();
    $receiver = actingAlumni();
    $connection = makeConnection($requester, $receiver, enConnectionStatus::PENDING);

    $this->postJson("/api/v1/connections/{$connection->id}/accept")
        ->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('message', 'Connection request accepted successfully.')
        ->assertJsonPath('data.status', 'accepted');

    $this->assertDatabaseHas('connections', [
        'id' => $connection->id,
        'status' => 'accepted',
    ]);
});

/**
 * The original requester cannot accept their own outgoing request.
 * Only the receiver is authorised to accept — this must return 422.
 */
test('requester cannot accept own connection request', function () {
    $requester = actingAlumni();
    $receiver = User::factory()->create();
    $connection = makeConnection($requester, $receiver, enConnectionStatus::PENDING);

    $this->postJson("/api/v1/connections/{$connection->id}/accept")->assertStatus(422);
});

// ===========================================================================
// POST /api/v1/connections/{connection}/reject  — reject connection
// ===========================================================================

/**
 * The receiver of a pending request can reject it.
 * The response must show the updated status as "rejected", and the
 * database record must be updated accordingly.
 */
test('receiver can reject pending connection', function () {
    $requester = User::factory()->create();
    $receiver = actingAlumni();
    $connection = makeConnection($requester, $receiver, enConnectionStatus::PENDING);

    $this->postJson("/api/v1/connections/{$connection->id}/reject")
        ->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('message', 'Connection request rejected successfully.')
        ->assertJsonPath('data.status', 'rejected');

    $this->assertDatabaseHas('connections', [
        'id' => $connection->id,
        'status' => 'rejected',
    ]);
});

// ===========================================================================
// DELETE /api/v1/connections/{connection}  — remove connection
// ===========================================================================

/**
 * Either party of an ACCEPTED connection can remove (delete) it.
 * The response must be 200 and the record must be hard-deleted from the DB.
 */
test('party can delete accepted connection', function () {
    $user = actingAlumni();
    $other = User::factory()->create();
    $connection = makeConnection($user, $other, enConnectionStatus::ACCEPTED);

    $this->deleteJson("/api/v1/connections/{$connection->id}")
        ->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('message', 'Connection deleted successfully.');

    $this->assertDatabaseMissing('connections', ['id' => $connection->id]);
});

/**
 * A PENDING connection cannot be deleted — only accepted connections
 * can be removed.  Attempting to delete a pending one must return 422.
 */
test('cannot delete pending connection', function () {
    $user = actingAlumni();
    $other = User::factory()->create();
    $connection = makeConnection($user, $other, enConnectionStatus::PENDING);

    $this->deleteJson("/api/v1/connections/{$connection->id}")->assertStatus(422);
});

/**
 * A third party who is not part of the connection cannot delete it.
 * The endpoint must return 403 Forbidden for outsiders.
 */
test('outsider cannot delete connection', function () {
    $requester = User::factory()->create();
    $receiver = User::factory()->create();
    $connection = makeConnection($requester, $receiver, enConnectionStatus::ACCEPTED);

    actingAlumni(); // unrelated third-party user

    $this->deleteJson("/api/v1/connections/{$connection->id}")->assertStatus(403);
});

// ===========================================================================
// POST /api/v1/connections/{connection}/block  — block connection
// ===========================================================================

/**
 * Either party of an ACCEPTED connection can block the other.
 * The connection status must be updated to "blocked" in the database.
 */
test('party can block accepted connection', function () {
    $user = actingAlumni();
    $other = User::factory()->create();
    $connection = makeConnection($user, $other, enConnectionStatus::ACCEPTED);

    $this->postJson("/api/v1/connections/{$connection->id}/block")
        ->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('message', 'Connection blocked successfully.');

    $this->assertDatabaseHas('connections', [
        'id' => $connection->id,
        'status' => 'blocked',
    ]);
});

/**
 * A PENDING connection cannot be blocked directly.
 * The caller must first accept the connection before blocking.
 * This must return 422.
 */
test('cannot block pending connection', function () {
    $user = actingAlumni();
    $other = User::factory()->create();
    $connection = makeConnection($user, $other, enConnectionStatus::PENDING);

    $this->postJson("/api/v1/connections/{$connection->id}/block")->assertStatus(422);
});

// ===========================================================================
// 404 — non-existent resources
// ===========================================================================

/**
 * Attempting to accept or delete a connection that does not exist
 * must return 404 Not Found for both operations.
 */
test('returns 404 for nonexistent connection', function () {
    actingAlumni();

    $this->postJson('/api/v1/connections/99999/accept')->assertStatus(404);
    $this->deleteJson('/api/v1/connections/99999')->assertStatus(404);
});
