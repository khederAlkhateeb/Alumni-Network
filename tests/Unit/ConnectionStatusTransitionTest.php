<?php

namespace Tests\Unit;

use App\Enums\enConnectionStatus;
use App\Models\Connection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for Connection model status transitions.
 *
 * Tests the lifecycle of connection requests from creation through
 * acceptance, rejection, or blocking, including timestamp handling
 * and relationship integrity.
 */
class ConnectionStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A newly created connection starts with pending status.
     */
    public function test_new_connection_has_pending_status(): void
    {
        $requester = User::factory()->create();
        $receiver = User::factory()->create();

        $connection = Connection::create([
            'requester_id' => $requester->id,
            'receiver_id' => $receiver->id,
            'status' => enConnectionStatus::PENDING,
        ]);

        $this->assertEquals(enConnectionStatus::PENDING, $connection->status);
        $this->assertNull($connection->accepted_at);
        $this->assertNull($connection->rejected_at);
    }

    /**
     * A connection can transition from pending to accepted status.
     */
    public function test_connection_transitions_from_pending_to_accepted(): void
    {
        $connection = $this->createConnection(enConnectionStatus::PENDING);

        $connection->update([
            'status' => enConnectionStatus::ACCEPTED,
            'accepted_at' => now(),
        ]);

        $connection->refresh();

        $this->assertEquals(enConnectionStatus::ACCEPTED, $connection->status);
        $this->assertNotNull($connection->accepted_at);
        $this->assertNull($connection->rejected_at);
    }

    /**
     * A connection can transition from pending to rejected status.
     */
    public function test_connection_transitions_from_pending_to_rejected(): void
    {
        $connection = $this->createConnection(enConnectionStatus::PENDING);

        $connection->update([
            'status' => enConnectionStatus::REJECTED,
            'rejected_at' => now(),
        ]);

        $connection->refresh();

        $this->assertEquals(enConnectionStatus::REJECTED, $connection->status);
        $this->assertNotNull($connection->rejected_at);
        $this->assertNull($connection->accepted_at);
    }

    /**
     * A connection can transition from pending to blocked status.
     */
    public function test_connection_transitions_from_pending_to_blocked(): void
    {
        $connection = $this->createConnection(enConnectionStatus::PENDING);

        $connection->update([
            'status' => enConnectionStatus::BLOCKED,
        ]);

        $connection->refresh();

        $this->assertEquals(enConnectionStatus::BLOCKED, $connection->status);
        $this->assertNull($connection->accepted_at);
        $this->assertNull($connection->rejected_at);
    }

    /**
     * An accepted connection can transition to blocked status.
     */
    public function test_connection_transitions_from_accepted_to_blocked(): void
    {
        $connection = $this->createConnection(enConnectionStatus::ACCEPTED, now());

        $previousAcceptedAt = $connection->accepted_at;

        $connection->update([
            'status' => enConnectionStatus::BLOCKED,
        ]);

        $connection->refresh();

        $this->assertEquals(enConnectionStatus::BLOCKED, $connection->status);
        $this->assertEquals($previousAcceptedAt, $connection->accepted_at);
    }

    /**
     * A rejected connection can transition to accepted status.
     */
    public function test_connection_transitions_from_rejected_to_accepted(): void
    {
        $connection = $this->createConnection(enConnectionStatus::REJECTED, null, now());

        $connection->update([
            'status' => enConnectionStatus::ACCEPTED,
            'accepted_at' => now(),
            'rejected_at' => null,
        ]);

        $connection->refresh();

        $this->assertEquals(enConnectionStatus::ACCEPTED, $connection->status);
        $this->assertNotNull($connection->accepted_at);
        $this->assertNull($connection->rejected_at);
    }

    /**
     * Status enum is properly cast to and from database.
     */
    public function test_status_is_cast_to_enum(): void
    {
        $connection = $this->createConnection(enConnectionStatus::PENDING);

        $this->assertInstanceOf(enConnectionStatus::class, $connection->status);
        $this->assertEquals('pending', $connection->status->value);
    }

    /**
     * Accepted_at and rejected_at are cast to datetime instances.
     */
    public function test_timestamps_are_cast_to_datetime(): void
    {
        $connection = $this->createConnection(
            enConnectionStatus::ACCEPTED,
            now()
        );

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $connection->accepted_at);
    }

    /**
     * Connection belongs to a requester (sender) user.
     */
    public function test_connection_belongs_to_requester(): void
    {
        $requester = User::factory()->create();
        $receiver = User::factory()->create();

        $connection = Connection::create([
            'requester_id' => $requester->id,
            'receiver_id' => $receiver->id,
            'status' => enConnectionStatus::PENDING,
        ]);

        $this->assertInstanceOf(User::class, $connection->sender);
        $this->assertEquals($requester->id, $connection->sender->id);
    }

    /**
     * Connection belongs to a receiver user.
     */
    public function test_connection_belongs_to_receiver(): void
    {
        $requester = User::factory()->create();
        $receiver = User::factory()->create();

        $connection = Connection::create([
            'requester_id' => $requester->id,
            'receiver_id' => $receiver->id,
            'status' => enConnectionStatus::PENDING,
        ]);

        $this->assertInstanceOf(User::class, $connection->receiver);
        $this->assertEquals($receiver->id, $connection->receiver->id);
    }

    /**
     * All status transitions are valid and persist correctly.
     */
    public function test_all_status_values_can_be_assigned(): void
    {
        $connection = $this->createConnection(enConnectionStatus::PENDING);

        foreach (enConnectionStatus::cases() as $status) {
            $connection->update(['status' => $status]);
            $connection->refresh();

            $this->assertEquals($status, $connection->status);
        }
    }

    /**
     * Connection can be created with all fillable attributes.
     */
    public function test_connection_has_correct_fillable_attributes(): void
    {
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

        $this->assertEquals($requester->id, $connection->requester_id);
        $this->assertEquals($receiver->id, $connection->receiver_id);
        $this->assertEquals(enConnectionStatus::ACCEPTED, $connection->status);
        $this->assertNotNull($connection->accepted_at);
        $this->assertNull($connection->rejected_at);
    }

    /**
     * Multiple connections between different user pairs are independent.
     */
    public function test_multiple_connections_are_independent(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

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

        $this->assertEquals(enConnectionStatus::ACCEPTED, $connection1->fresh()->status);
        $this->assertEquals(enConnectionStatus::PENDING, $connection2->fresh()->status);
    }

    /**
     * Helper method to create a connection with specified status and timestamps.
     */
    private function createConnection(
        enConnectionStatus $status,
        ?\Illuminate\Support\Carbon $acceptedAt = null,
        ?\Illuminate\Support\Carbon $rejectedAt = null
    ): Connection {
        $requester = User::factory()->create();
        $receiver = User::factory()->create();

        return Connection::create([
            'requester_id' => $requester->id,
            'receiver_id' => $receiver->id,
            'status' => $status,
            'accepted_at' => $acceptedAt,
            'rejected_at' => $rejectedAt,
        ]);
    }
}
