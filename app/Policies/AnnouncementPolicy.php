<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;

/**
 * Administrators and instructors may write announcements.
 * Everyone can only change their own; administrators may also delete any (moderation).
 */
class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['manager', 'instructor'], true);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $this->viewAny($user) && $announcement->author_id === $user->user_id;
    }

    public function publish(User $user, Announcement $announcement): bool
    {
        return $this->update($user, $announcement) && ! $announcement->isPublished();
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $user->role === 'manager' || $this->update($user, $announcement);
    }
}
