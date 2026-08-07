<?php

namespace App\Models;

//use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Builders\UserBuilder;
use App\Enums\enConnectionStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

/**
 * Represents an application user (super_admin, uni_admin, alumni, or student).
 *
 * A single `users` table backs all roles; role-specific data lives in
 * dedicated profile tables (StudentProfile, AlumniProfile, UniversityAdmin),
 * each linked back to this model via a one-to-one relation.
 */
#[Fillable(['name', 'email', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token', 'updated_at'])]
class User extends Authenticatable
{

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    use HasRoles, HasApiTokens;

    /**
     * Spatie permission/role lookups must match RoleAndPermissionSeeder (guard: api).
     */
    protected string $guard_name = 'api';

    // append the Role label accessor
    protected $appends = ['role_label'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The student profile associated with this user, if the user's
     * role is 'student'.
     */

    // return the label of the rule for show
    protected function roleLabel(): Attribute
    {
        return Attribute::get(
            fn(): ?string => match ($this->getRoleNames()->first()) {
                'super_admin' => 'super admin',
                'uni_admin' => 'university admin',
                'alumni' => 'alumni',
                'student' => 'student',
                default => $this->getRoleNames()->first(),
            }
        );
    }

    // Relatioships
    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    /**
     * The university-admin record associated with this user, if the
     * user's role is 'uni_admin'.
     */
    public function universityAdmin(): HasOne
    {
        return $this->hasOne(UniversityAdmin::class);
    }

    /**
     * The alumni profile associated with this user, if the user's
     * role is 'alumni'.
     */
    public function alumniProfile(): HasOne
    {
        return $this->hasOne(AlumniProfile::class, 'user_id');
    }

    /**
     * All comments authored by this user, across all posts
     * (comments.user_id is the actual foreign key).
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * All notifications for this user.
     */
    public function notifications()
    {
        return $this->hasMany(\App\Models\Notification::class, 'user_id');
    }

    /**
     * All unread notifications for this user.
     */
    public function unreadNotifications()
    {
        return $this->notifications()->whereNull('read_at');
    }

    /**
     * All reactions made by this user, across posts and comments.
     *
     * Reaction is polymorphic on the "reactable" side (post/comment),
     * but from the User's side this is a plain one-to-many: the real
     * foreign key (reactions.user_id) lives on the reactions table,
     * and a single user can create many reactions — so User is the
     * "one" side (hasMany), never the "belongsTo" side.
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    /**
     * Get the IDs of users this user is connected to (accepted only),
     * regardless of who sent the original connection request.
     */
    public function connectedUserIds(): array
    {
        return Connection::query()
            ->where('status', enConnectionStatus::ACCEPTED->value)
            ->where(function ($query) {
                $query->where('requester_id', $this->id)
                    ->orWhere('receiver_id', $this->id);
            })
            ->get()
            ->map(fn($c) => $c->requester_id === $this->id ? $c->receiver_id : $c->requester_id)
            ->toArray();
    }
    public function jobListings(): HasMany
    {
        return $this->hasMany(JobListing::class, 'posted_by_user_id');
    }

    public function jobApplications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'applicant_id');
    }
    public function receivedMentorshipRequests(): HasMany
    {
        return $this->hasMany(MentorshipRequest::class, 'mentor_id');
    }

    public function sentMentorshipRequests(): HasMany
    {
        return $this->hasMany(MentorshipRequest::class, 'mentee_id');
    }
    public function mentorshipPrograms(): BelongsToMany
    {
        return $this->belongsToMany(
            MentorshipProgram::class,
            'mentorship_requests',
            'mentor_id',
            'program_id'
        )->distinct();
    }
    public function hasReachedLimit(int $programId): bool
    {
        $program = MentorshipProgram::find($programId);
        if (!$program) return false;
        $activeCount = MentorshipRequest::query()
            ->where('mentor_id', $this->id)
            ->where('program_id', $programId)
            ->whereIn('status', ['accepted'])
            ->count();

        return $activeCount >= $program->mentor_per_mentees_max;
    }
    /**
     * All messages sent by this user (across all conversations).
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * All conversations this user is part of, regardless of whether
     * they're stored as user_one or user_two.
     *
     * Not a standard Eloquent relation (hasMany assumes a single FK
     * column) — this is a plain query method, since a user can appear
     * in either column depending on who has the smaller ID.
     */
    public function conversations()
    {
        return Conversation::where('user_one_id', $this->id)
            ->orWhere('user_two_id', $this->id)
            ->get();
    }
}
