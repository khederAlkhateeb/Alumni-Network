<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Event;
use App\Models\University;

/*
|--------------------------------------------------------------------------
| Event Authorization Policy
|--------------------------------------------------------------------------
|
| Defines authorization rules for viewing, creating, and managing events.
| Regular authenticated users may view events as long as their account
| is active. Only University Admins belonging to the same university
| as the event/university in question may create or manage events.
|
*/

class EventPolicy
{
    /**
     * Determine if the given user's account is active.
     *
     * @param  User  $user
     * @return bool
     */
    private function isActive(User $user): bool
    {
        return (bool) $user->is_active;
    }

    /**
     * Determine if the user can view the list of events for a university.
     *
     * @param  User  $user
     * @param  University  $university
     * @return bool
     */
    public function viewAny(User $user, University $university): bool
    {
        return $this->isActive($user);
    }

    /**
     * Determine if the user can view a specific event.
     *
     * @param  User  $user
     * @param  Event  $event
     * @return bool
     */
    public function view(User $user, Event $event): bool
    {
        return $this->isActive($user);
    }

    /**
     * Determine if the user can manage (update/delete/attend) an event.
     *
     * Only a University Admin who belongs to the same university as
     * the event is authorized to manage it.
     *
     * @param  User  $user
     * @param  Event  $event
     * @return bool
     */
    public function manage(User $user, Event $event): bool
    {
        return $user->hasRole('uni_admin')
            && $user->universityAdmin?->university_id === $event->university_id;
    }

    /**
     * Determine if the user can create a new event for a university.
     *
     * Only a University Admin who belongs to the given university
     * is authorized to create events for it.
     *
     * @param  User  $user
     * @param  University  $university
     * @return bool
     */
    public function create(User $user, University $university): bool
    {
        return $user->hasRole('uni_admin') && $user->university_id === $university->id;
    }
}
