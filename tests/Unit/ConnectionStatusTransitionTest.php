<?php

use App\Enums\enConnectionStatus;
use App\Models\Connection;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Unit tests for Connection model status transitions.
 *
 * Tests the lifecycle of connection requests from creation through
 * acceptance, rejection, or blocking, including timestamp handling
 * and relationship integrity.
 */

// ── Helper ────────────────────────────────────────────────────────────────────

function makeConnection(
    enConnectionStatus $status,
    ?Carbon $acceptedAt = null,
    ?Carbon $rejectedAt = null,
): Connection {
    return Connection::create([
        'requester_id' => User::factory()->create()->id,
        'receiver_id' => User::factory()->create()->id,
        'status' => $status,
        'accepted_at' => $acceptedAt,
        'rejected_at' => $rejectedAt,
    ]);
}

// ── Status creation ───────────────────────────────────────────────────────────

test('new connection has pending status', function () {
    $requester = User::factory()->create();
    $receiver = User::factory()->create();

    $connection = Connection::create([
        'requester_id' => $requester->id,
        'receiver_id' => $receiver->id,
        'status' => enConnectionStatus::PENDING,
    ]);

    expect($connection->status)->toBe(enConnectionStatus::PENDING)
        ->and($connection->accepted_at)->toBeNull()
        ->and($connection->rejected_at)->toBeNull();
});

// ── Status transitions ────────────────────────────────────────────────────────

test('connection transitions from pending to accepted', function () {
    $connection = makeConnection(enConnectionStatus::PENDING);

    $connection->update([
        'status' => enConnectionStatus::ACCEPTED,
        'accepted_at' => now(),
    ]);

    $connection->refresh();

    expect($connection->status)->toBe(enConnectionStatus::ACCEPTED)
        ->and($connection->accepted_at)->not->toBeNull()
        ->and($connection->rejected_at)->toBeNull();
});

test('connection transitions from pending to rejected', function () {
    $connection = makeConnection(enConnectionStatus::PENDING);

    $connection->update([
        'status' => enConnectionStatus::REJECTED,
        'rejected_at' => now(),
    ]);

    $connection->refresh();

    expect($connection->status)->toBe(enConnectionStatus::REJECTED)
        ->and($connection->rejected_at)->not->toBeNull()
        ->and($connection->accepted_at)->toBeNull();
});

test('connection transitions from pending to blocked', function () {
    $connection = makeConnection(enConnectionStatus::PENDING);

    $connection->update(['status' => enConnectionStatus::BLOCKED]);
    $connection->refresh();

    expect($connection->status)->toBe(enConnectionStatus::BLOCKED)
        ->and($connection->accepted_at)->toBeNull()
        ->and($connection->rejected_at)->toBeNull();
});

test('connection transitions from accepted to blocked', function () {
    $connection = makeConnection(enConnectionStatus::ACCEPTED, now());
    $previousAcceptedAt = $connection->accepted_at;

    $connection->update(['status' => enConnectionStatus::BLOCKED]);
    $connection->refresh();

    expect($connection->status)->toBe(enConnectionStatus::BLOCKED)
        ->and($connection->accepted_at->eq($previousAcceptedAt))->toBeTrue();
});

test('connection transitions from rejected to accepted', function () {
    $connection = makeConnection(enConnectionStatus::REJECTED, null, now());

    $connection->update([
        'status' => enConnectionStatus::ACCEPTED,
        'accepted_at' => now(),
        'rejected_at' => null,
    ]);

    $connection->refresh();

    expect($connection->status)->toBe(enConnectionStatus::ACCEPTED)
        ->and($connection->accepted_at)->not->toBeNull()
        ->and($connection->rejected_at)->toBeNull();
});

test('all status values can be assigned', function () {
    $connection = makeConnection(enConnectionStatus::PENDING);

    foreach (enConnectionStatus::cases() as $status) {
        $connection->update(['status' => $status]);
        $connection->refresh();

        expect($connection->status)->toBe($status);
    }
});

// ── Casting ───────────────────────────────────────────────────────────────────

test('status is cast to enum', function () {
    $connection = makeConnection(enConnectionStatus::PENDING);

    expect($connection->status)
        ->toBeInstanceOf(enConnectionStatus::class)
        ->and($connection->status->value)->toBe('pending');
});

test('accepted_at is cast to Carbon datetime', function () {
    $connection = makeConnection(enConnectionStatus::ACCEPTED, now());

    expect($connection->accepted_at)->toBeInstanceOf(Carbon::class);
});

// ── Relationships ─────────────────────────────────────────────────────────────

test('connection belongs to requester (sender)', function () {
    $requester = User::factory()->create();
    $receiver = User::factory()->create();

    $connection = Connection::create([
        'requester_id' => $requester->id,
        'receiver_id' => $receiver->id,
        'status' => enConnectionStatus::PENDING,
    ]);

    expect($connection->sender)->toBeInstanceOf(User::class)
        ->and($connection->sender->id)->toBe($requester->id);
});

test('connection belongs to receiver', function () {
    $requester = User::factory()->create();
    $receiver = User::factory()->create();

    $connection = Connection::create([
        'requester_id' => $requester->id,
        'receiver_id' => $receiver->id,
        'status' => enConnectionStatus::PENDING,
    ]);

    expect($connection->receiver)->toBeInstanceOf(User::class)
        ->and($connection->receiver->id)->toBe($receiver->id);
});

// ── Fillable & integrity ──────────────────────────────────────────────────────

test('connection has correct fillable attributes', function () {
    $requester = User::factory()->create();
    $receiver = User::factory()->create();
    $acceptedAt = now();

    $connection = Connection::create([
        'requester_id' => $requester->id,
        'receiver_id' => $receiver->id,
        'status' => enConnectionStatus::ACCEPTED,
        'accepted_at' => $acceptedAt,
        'rejected_at' => null,
    ]);

    expect($connection->requester_id)->toBe($requester->id)
        ->and($connection->receiver_id)->toBe($receiver->id)
        ->and($connection->status)->toBe(enConnectionStatus::ACCEPTED)
        ->and($connection->accepted_at)->not->toBeNull()
        ->and($connection->rejected_at)->toBeNull();
});

test('multiple connections between different user pairs are independent', function () {
    [$user1, $user2, $user3] = User::factory()->count(3)->create()->all();

    $connection1 = Connection::create([
        'requester_id' => $user1->id,
        'receiver_id' => $user2->id,
        'status' => enConnectionStatus::ACCEPTED,
        'accepted_at' => now(),
    ]);

    $connection2 = Connection::create([
        'requester_id' => $user2->id,
        'receiver_id' => $user3->id,
        'status' => enConnectionStatus::PENDING,
    ]);

    expect($connection1->fresh()->status)->toBe(enConnectionStatus::ACCEPTED)
        ->and($connection2->fresh()->status)->toBe(enConnectionStatus::PENDING);
});
