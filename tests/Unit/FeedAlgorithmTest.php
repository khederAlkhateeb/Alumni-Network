<?php

namespace Tests\Unit;

use App\Enums\enConnectionStatus;
use App\Enums\PostVisibility;
use App\Models\AlumniProfile;
use App\Models\Connection;
use App\Models\Faculty;
use App\Models\Major;
use App\Models\Post;
use App\Models\University;
use App\Models\User;
use App\V1\Actions\Feed\GetFeedAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for the Feed algorithm (GetFeedAction).
 *
 * Verifies that the feed correctly aggregates posts from its three
 * documented sources — accepted connections, university announcements,
 * and other alumni at the same university — while excluding anything
 * that doesn't belong to any of those sources, avoiding duplicates,
 * and preserving latest-first ordering.
 */
class FeedAlgorithmTest extends TestCase
{
    use RefreshDatabase;

    private GetFeedAction $action;
    private University $university;
    private Faculty $faculty;
    private Major $major;
    private User $viewer;

    /**
     * Build a minimal academic hierarchy (University -> Faculty -> Major)
     * and the "viewer" user whose feed is under test in every case below.
     */
    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Cache::flush();
        $this->action = app(GetFeedAction::class);

        $this->university = University::factory()->create();
        $this->faculty = Faculty::factory()->create(['university_id' => $this->university->id]);
        $this->major = Major::factory()->create(['faculty_id' => $this->faculty->id]);

        $this->viewer = $this->createAlumniUser();
    }

    /**
     * Create a user with an active AlumniProfile tied to the shared
     * test university/faculty/major, unless a different major is given.
     */
    private function createAlumniUser(?Major $major = null): User
    {
        $user = User::factory()->create();

        AlumniProfile::factory()->create([
            'user_id' => $user->id,
            'major_id' => ($major ?? $this->major)->id,
            'status' => 'active',
        ]);

        return $user;
    }

    /**
     * Create an accepted Connection between two users, regardless of
     * which one is stored as requester vs receiver.
     */
    private function createAcceptedConnection(User $a, User $b): Connection
    {
        return Connection::factory()->create([
            'requester_id' => $a->id,
            'receiver_id' => $b->id,
            'status' => enConnectionStatus::ACCEPTED->value,
        ]);
    }

    /**
     * The feed includes a post authored by a user the viewer has an
     * accepted connection with, even when the post's visibility is
     * the most restrictive option (connections-only).
     */
    public function test_feed_includes_posts_from_accepted_connections(): void
    {
        $connectedUser = $this->createAlumniUser();
        $this->createAcceptedConnection($this->viewer, $connectedUser);

        $post = Post::factory()->create([
            'user_id' => $connectedUser->id,
            'visibility' => PostVisibility::Connections->value,
        ]);

        $result = $this->action->handle($this->viewer);

        $this->assertTrue(
            collect($result->items())->contains(fn ($p) => $p->id === $post->id)
        );
    }

    /**
     * The feed does NOT include a connections-only post from a user
     * the viewer has no accepted connection with.
     */
    public function test_feed_excludes_connections_only_posts_from_non_connections(): void
    {
        $strangerUser = $this->createAlumniUser();

        $post = Post::factory()->create([
            'user_id' => $strangerUser->id,
            'visibility' => PostVisibility::Connections->value,
        ]);

        $result = $this->action->handle($this->viewer);

        $this->assertFalse(
            collect($result->items())->contains(fn ($p) => $p->id === $post->id)
        );
    }

    /**
     * The feed includes a "university" visibility post from any user
     * at the viewer's own university, even without a connection between
     * them (university-wide announcements source).
     */
    public function test_feed_includes_university_announcements_from_same_university(): void
    {
        $announcer = $this->createAlumniUser();

        $post = Post::factory()->create([
            'user_id' => $announcer->id,
            'visibility' => PostVisibility::University->value,
        ]);

        $result = $this->action->handle($this->viewer);

        $this->assertTrue(
            collect($result->items())->contains(fn ($p) => $p->id === $post->id)
        );
    }

    /**
     * The feed excludes a "university" visibility post authored by
     * someone from a different university than the viewer.
     */
    public function test_feed_excludes_university_announcements_from_other_universities(): void
    {
        $otherUniversity = University::factory()->create();
        $otherFaculty = Faculty::factory()->create(['university_id' => $otherUniversity->id]);
        $otherMajor = Major::factory()->create(['faculty_id' => $otherFaculty->id]);

        $foreignUser = $this->createAlumniUser($otherMajor);

        $post = Post::factory()->create([
            'user_id' => $foreignUser->id,
            'visibility' => PostVisibility::University->value,
        ]);

        $result = $this->action->handle($this->viewer);

        $this->assertFalse(
            collect($result->items())->contains(fn ($p) => $p->id === $post->id)
        );
    }

    /**
     * The feed includes a "public" visibility post from any alumni at
     * the same university, even without a connection between them
     * (same-university alumni source).
     */
    public function test_feed_includes_public_posts_from_same_university_alumni(): void
    {
        $sameUniversityAlumni = $this->createAlumniUser();

        $post = Post::factory()->create([
            'user_id' => $sameUniversityAlumni->id,
            'visibility' => PostVisibility::Public->value,
        ]);

        $result = $this->action->handle($this->viewer);

        $this->assertTrue(
            collect($result->items())->contains(fn ($p) => $p->id === $post->id)
        );
    }

    /**
     * The feed excludes a "public" visibility post from an alumni at
     * a different university than the viewer.
     */
    public function test_feed_excludes_public_posts_from_other_universities(): void
    {
        $otherUniversity = University::factory()->create();
        $otherFaculty = Faculty::factory()->create(['university_id' => $otherUniversity->id]);
        $otherMajor = Major::factory()->create(['faculty_id' => $otherFaculty->id]);

        $foreignUser = $this->createAlumniUser($otherMajor);

        $post = Post::factory()->create([
            'user_id' => $foreignUser->id,
            'visibility' => PostVisibility::Public->value,
        ]);

        $result = $this->action->handle($this->viewer);

        $this->assertFalse(
            collect($result->items())->contains(fn ($p) => $p->id === $post->id)
        );
    }

    /**
     * A post that satisfies more than one feed source at once (e.g. a
     * connection's public post, which matches both the "connections"
     * and "same-university alumni" criteria) appears exactly once in
     * the feed — the OR-based query must not produce duplicate rows.
     */
    public function test_feed_does_not_duplicate_posts_matching_multiple_sources(): void
    {
        $connectedUser = $this->createAlumniUser();
        $this->createAcceptedConnection($this->viewer, $connectedUser);

        $post = Post::factory()->create([
            'user_id' => $connectedUser->id,
            'visibility' => PostVisibility::Public->value,
        ]);

        $result = $this->action->handle($this->viewer);

        $matchingCount = collect($result->items())
            ->filter(fn ($p) => $p->id === $post->id)
            ->count();

        $this->assertSame(1, $matchingCount);
    }

    /**
     * Feed results are ordered by creation date, most recent first,
     * regardless of which of the three sources each post came from.
     */
    public function test_feed_is_ordered_by_latest_first(): void
    {
        $connectedUser = $this->createAlumniUser();
        $this->createAcceptedConnection($this->viewer, $connectedUser);

        $olderPost = Post::factory()->create([
            'user_id' => $connectedUser->id,
            'visibility' => PostVisibility::Connections->value,
            'created_at' => now()->subDays(3),
        ]);

        $newerPost = Post::factory()->create([
            'user_id' => $connectedUser->id,
            'visibility' => PostVisibility::Connections->value,
            'created_at' => now()->subMinutes(5),
        ]);

        $result = $this->action->handle($this->viewer);
        $ids = collect($result->items())->pluck('id')->values();

        $this->assertLessThan(
            $ids->search($olderPost->id),
            $ids->search($newerPost->id)
        );
    }

    /**
     * A pending connection request (not yet accepted) does not grant
     * feed visibility into that user's connections-only posts.
     */
    public function test_feed_excludes_posts_from_pending_connections(): void
    {
        $pendingUser = $this->createAlumniUser();

        Connection::factory()->create([
            'requester_id' => $this->viewer->id,
            'receiver_id' => $pendingUser->id,
            'status' => enConnectionStatus::PENDING->value,
        ]);

        $post = Post::factory()->create([
            'user_id' => $pendingUser->id,
            'visibility' => PostVisibility::Connections->value,
        ]);

        $result = $this->action->handle($this->viewer);

        $this->assertFalse(
            collect($result->items())->contains(fn ($p) => $p->id === $post->id)
        );
    }

    /**
     * The connection lookup is direction-agnostic: the feed includes
     * connections-only posts whether the viewer was the original
     * requester or the receiver of the accepted connection.
     */
    public function test_feed_includes_connections_regardless_of_request_direction(): void
    {
        $connectedUser = $this->createAlumniUser();

        // Here the connected user is the requester, and the viewer is
        // the receiver — the reverse of test_feed_includes_posts_from_accepted_connections.
        Connection::factory()->create([
            'requester_id' => $connectedUser->id,
            'receiver_id' => $this->viewer->id,
            'status' => enConnectionStatus::ACCEPTED->value,
        ]);

        $post = Post::factory()->create([
            'user_id' => $connectedUser->id,
            'visibility' => PostVisibility::Connections->value,
        ]);

        $result = $this->action->handle($this->viewer);

        $this->assertTrue(
            collect($result->items())->contains(fn ($p) => $p->id === $post->id)
        );
    }
}
