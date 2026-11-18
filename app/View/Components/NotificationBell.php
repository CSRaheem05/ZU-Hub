<?php

namespace App\View\Components;

use App\Models\UserNotification;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Glass notification bell for the shared header: unread badge + a popover with the latest
 * notifications. Only the signed-in user's own inbox is ever queried.
 *
 * Usage: <x-notification-bell />
 */
class NotificationBell extends Component
{
    public const LIMIT = 8;

    /** @var Collection<int, UserNotification> */
    public Collection $items;
    public int $unread = 0;
    /** Unread, still-active Important/Urgent admin announcements (drives the ring + red glow). */
    public int $alerts = 0;
    /** Strongest unread alert priority ('urgent' | 'important') or null; colours the bell and its badge. */
    public ?string $level = null;

    public function __construct()
    {
        $userId = auth()->user()?->user_id;

        $this->items = $userId ? UserNotification::query()->inbox($userId)->limit(self::LIMIT)->get() : collect();
        $this->unread = $userId ? UserNotification::unreadCountFor($userId) : 0;
        $this->alerts = $userId ? UserNotification::alertCountFor($userId) : 0;
        $this->level = $userId ? UserNotification::alertLevelFor($userId) : null;
    }

    public function render(): View
    {
        return view('components.notification-bell');
    }
}
