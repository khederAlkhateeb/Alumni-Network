<?php

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
use Illuminate\Support\Facades\Cache;

/**
 * Unit tests for the Feed algorithm (GetFeedAction), as required by
 * the spec's "Feed Algorithm Logic ▸ Unit Tests" item.
 *
 * Verifies that the feed correctly aggregates posts from its three
 * documented sources — accepted connections, university announcements,
 * and other alumni at the same university — while excluding anything
 * that doesn't belong to any of those sources, avoiding duplicates,
 * and preserving latest-first ordering.
 */
uses(RefreshDatabase::class);

/**
 * Build a minimal academic hierarchy (University -> Faculty -> Major)
 * and the "viewer" user whose feed is under test in every case below.
 */
beforeEach(function () {
    Cache::flush();

    $this->action = app(GetFeedAction::class);

    $this->university = University::factory()->create();
    $this->faculty = Faculty::factory()->create(['university_id' => $this->university->id]);
    $this->major = Major::factory()->create(['faculty_id' => $this->faculty->id]);

    $this->viewer = createFeedAlumniUser($this->major);
});

/**
 * Create a user with an active AlumniProfile tied to the given major
 * (defaults to the shared test major via $this->major when omitted).
 */
function createFeedAlumniUser(?Major $major = null): User
{
    $user = User::factory()->create();

    AlumniProfile::factory()->create([
        'user_id' => $user->id,
        'major_id' => ($major ?? test()->major)->id,
        'status' => 'active',
    ]);

    return $user;
}

/**
 * Create an accepted Connection between two users, regardless of
 * which one is stored as requester vs receiver.
 */
function createFeedAcceptedConnection(User $a, User $b): Connection
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
it('includes posts from accepted connections', function () {
    $connectedUser = createFeedAlumniUser($this->major);
    createFeedAcceptedConnection($this->viewer, $connectedUser);

    $post = Post::factory()->create([
        'user_id' => $connectedUser->id,
        'visibility' => PostVisibility::Connections->value,
    ]);

    $result = $this->action->handle($this->viewer);

    expect(collect($result->items())->contains(fn ($p) => $p->id === $post->id))->toBeTrue();
});

/**
 * The feed does NOT include a connections-only post from a user
 * the viewer has no accepted connection with.
 */
it('excludes connections-only posts from non-connections', function () {
    $strangerUser = createFeedAlumniUser($this->major);

    $post = Post::factory()->create([
        'user_id' => $strangerUser->id,
        'visibility' => PostVisibility::Connections->value,
    ]);

    $result = $this->action->handle($this->viewer);

    expect(collect($result->items())->contains(fn ($p) => $p->id === $post->id))->toBeFalse();
});

/**
 * The feed includes a "university" visibility post from any user
 * at the viewer's own university, even without a connection between
 * them (university-wide announcements source).
 */
it('includes university announcements from the same university', function () {
    $announcer = createFeedAlumniUser($this->major);

    $post = Post::factory()->create([
        'user_id' => $announcer->id,
        'visibility' => PostVisibility::University->value,
    ]);

    $result = $this->action->handle($this->viewer);

    expect(collect($result->items())->contains(fn ($p) => $p->id === $post->id))->toBeTrue();
});

/**
 * The feed excludes a "university" visibility post authored by
 * someone from a different university than the viewer.
 */
it('excludes university announcements from other universities', function () {
    $otherUniversity = University::factory()->create();
    $otherFaculty = Faculty::factory()->create(['university_id' => $otherUniversity->id]);
    $otherMajor = Major::factory()->create(['faculty_id' => $otherFaculty->id]);

    $foreignUser = createFeedAlumniUser($otherMajor);

    $post = Post::factory()->create([
        'user_id' => $foreignUser->id,
        'visibility' => PostVisibility::University->value,
    ]);

    $result = $this->action->handle($this->viewer);

    expect(collect($result->items())->contains(fn ($p) => $p->id === $post->id))->toBeFalse();
});

/**
 * The feed includes a "public" visibility post from any alumni at
 * the same university, even without a connection between them
 * (same-university alumni source).
 */
it('includes public posts from same-university alumni', function () {
    $sameUniversityAlumni = createFeedAlumniUser($this->major);

    $post = Post::factory()->create([
        'user_id' => $sameUniversityAlumni->id,
        'visibility' => PostVisibility::Public->value,
    ]);

    $result = $this->action->handle($this->viewer);

    expect(collect($result->items())->contains(fn ($p) => $p->id === $post->id))->toBeTrue();
});

/**
 * The feed excludes a "public" visibility post from an alumni at
 * a different university than the viewer.
 */
it('excludes public posts from other universities', function () {
    $otherUniversity = University::factory()->create();
    $otherFaculty = Faculty::factory()->create(['university_id' => $otherUniversity->id]);
    $otherMajor = Major::factory()->create(['faculty_id' => $otherFaculty->id]);

    $foreignUser = createFeedAlumniUser($otherMajor);

    $post = Post::factory()->create([
        'user_id' => $foreignUser->id,
        'visibility' => PostVisibility::Public->value,
    ]);

    $result = $this->action->handle($this->viewer);

    expect(collect($result->items())->contains(fn ($p) => $p->id === $post->id))->toBeFalse();
});

/**
 * A post that satisfies more than one feed source at once (e.g. a
 * connection's public post, which matches both the "connections"
 * and "same-university alumni" criteria) appears exactly once in
 * the feed — the OR-based query must not produce duplicate rows.
 */
it('does not duplicate a post matching multiple feed sources', function () {
    $connectedUser = createFeedAlumniUser($this->major);
    createFeedAcceptedConnection($this->viewer, $connectedUser);

    $post = Post::factory()->create([
        'user_id' => $connectedUser->id,
        'visibility' => PostVisibility::Public->value,
    ]);

    $result = $this->action->handle($this->viewer);

    $matchingCount = collect($result->items())
        ->filter(fn ($p) => $p->id === $post->id)
        ->count();

    expect($matchingCount)->toBe(1);
});

/**
 * Feed results are ordered by creation date, most recent first,
 * regardless of which of the three sources each post came from.
 */
it('orders feed results by latest first', function () {
    $connectedUser = createFeedAlumniUser($this->major);
    createFeedAcceptedConnection($this->viewer, $connectedUser);

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

    expect($ids->search($newerPost->id))->toBeLessThan($ids->search($olderPost->id));
});

/**
 * A pending connection request (not yet accepted) does not grant
 * feed visibility into that user's connections-only posts.
 */
it('excludes posts from pending (not yet accepted) connections', function () {
    $pendingUser = createFeedAlumniUser($this->major);

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

    expect(collect($result->items())->contains(fn ($p) => $p->id === $post->id))->toBeFalse();
});

/**
 * The connection lookup is direction-agnostic: the feed includes
 * connections-only posts whether the viewer was the original
 * requester or the receiver of the accepted connection.
 */
it('includes connections regardless of request direction', function () {
    $connectedUser = createFeedAlumniUser($this->major);

    // Here the connected user is the requester, and the viewer is
    // the receiver — the reverse of the "includes posts from accepted
    // connections" test above.
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

    expect(collect($result->items())->contains(fn ($p) => $p->id === $post->id))->toBeTrue();
});
