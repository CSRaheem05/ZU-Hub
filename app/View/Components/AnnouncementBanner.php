<?php

namespace App\View\Components;

use App\Models\UserNotification;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * The glass slide-down banner for active Important/Urgent ADMIN announcements.
 *
 * Shown to students and instructors only, and only once per announcement per user: the
 * notification must be unread, still active, and its `alert_seen_at` empty. The browser reports
 * the alerts it displayed (NotificationController::alertsSeen), so navigating to another page
 * does not pop the same announcement up again. The bell keeps glowing until it is read.
 *
 * Usage: <x-announcement-banner />  (already part of <x-site-header />)
 */
class AnnouncementBanner extends Component
{
    public const MAX_SHOWN = 3;

    /** @var Collection<int, UserNotification> the alerts to display (most urgent first) */
    public Collection $items;
    /** Everything that qualified, so all of it is marked as announced, not only what fit. */
    public array $ids = [];
    public int $more = 0;

    public function __construct()
    {
        $user = auth()->user();
        $this->items = collect();

        if (! $user || ! in_array($user->role, ['student', 'instructor'], true)) {
            return;
        }

        $candidates = UserNotification::query()
            ->where('user_id', $user->user_id)
            ->alerting()
            ->whereNull('alert_seen_at')
            ->with(['announcement.author:user_id,name,role'])
            ->get()
            ->sortBy([
                fn ($a, $b) => ($b->announcement->priority === 'urgent') <=> ($a->announcement->priority === 'urgent'),
                fn ($a, $b) => $b->created_at <=> $a->created_at,
            ])
            ->values();

        $this->ids = $candidates->pluck('notification_id')->all();
        $this->items = $candidates->take(self::MAX_SHOWN);
        $this->more = max($candidates->count() - $this->items->count(), 0);
    }

    public function shouldRender(): bool
    {
        return $this->items->isNotEmpty();
    }

    public function render(): View
    {
        return view('components.announcement-banner');
    }
}
