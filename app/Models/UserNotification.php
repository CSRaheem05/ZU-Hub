<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * One row of the existing `notifications` table = one recipient's copy of an announcement.
 * (Named UserNotification to avoid clashing with Illuminate\Notifications.)
 *
 * Visibility rules live here, so every consumer (bell, inbox, banner, homepage) agrees:
 *   visible   the announcement is published and its go-live time has passed
 *   active    visible and not expired (only active notifications count as unread / alerts)
 *   alerting  active + unread + an Important/Urgent ADMIN announcement (bell glow, banner)
 */
class UserNotification extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'notification_id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['user_id', 'offering_id', 'announcement_id', 'message', 'is_read', 'read_at', 'alert_seen_at', 'created_at'];

    protected function casts(): array
    {
        return ['is_read' => 'boolean', 'read_at' => 'datetime', 'alert_seen_at' => 'datetime', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (UserNotification $n) {
            $n->notification_id ??= (string) Str::uuid();
        });
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class, 'announcement_id', 'announcement_id');
    }

    /** Backend recipient filter: a user can only ever query their own inbox (and only what is live for them). */
    public function scopeInbox(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId)
            ->visible()
            ->with(['announcement.author:user_id,name,role', 'announcement.offering:offering_id,title'])
            ->orderByDesc('created_at');
    }

    /** Hides scheduled (not yet live) announcements. Rows without an announcement are legacy and stay visible. */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('notifications.announcement_id')
                ->orWhereHas('announcement', fn (Builder $a) => $a->visibleNow());
        });
    }

    /** Visible and not expired. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('notifications.announcement_id')
                ->orWhereHas('announcement', fn (Builder $a) => $a->activeNow());
        });
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('notifications.is_read', false);
    }

    /** Unread, active, Important/Urgent and written by an administrator. */
    public function scopeAlerting(Builder $query): Builder
    {
        return $query->unread()->whereHas('announcement', function (Builder $a) {
            $a->activeNow()->where('author_role', 'manager')->whereIn('priority', ['important', 'urgent']);
        });
    }

    public static function unreadCountFor(string $userId): int
    {
        return static::query()->where('user_id', $userId)->active()->unread()->count();
    }

    public static function alertCountFor(string $userId): int
    {
        return static::query()->where('user_id', $userId)->alerting()->count();
    }

    /** The strongest priority among the user's unread, active alerts ('urgent' | 'important'), or null when there are none. */
    public static function alertLevelFor(string $userId): ?string
    {
        $priorities = static::query()->where('user_id', $userId)->alerting()
            ->with('announcement:announcement_id,priority')->get()
            ->map(fn (self $n) => $n->announcement?->priority);

        return $priorities->contains('urgent') ? 'urgent' : ($priorities->contains('important') ? 'important' : null);
    }

    public function markRead(): void
    {
        if (! $this->is_read) {
            $this->forceFill(['is_read' => true, 'read_at' => now(), 'alert_seen_at' => $this->alert_seen_at ?? now()])->save();
        }
    }

    public function title(): string
    {
        return $this->announcement?->title ?? $this->message; // legacy rows only have `message`
    }

    public function isExpired(): bool
    {
        return $this->announcement?->isExpired() === true;
    }

    /** Should this notification currently make the bell glow / ring? */
    public function isAlerting(): bool
    {
        $a = $this->announcement;

        return ! $this->is_read && $a && $a->isLive() && $a->isAlertWorthy();
    }

    /** Compact payload for the homepage bell (which is rendered by public/js/home.js). */
    public static function homepageFeed(string $userId, int $limit = 8): array
    {
        $items = static::query()->inbox($userId)->limit($limit)->get()->map(function (UserNotification $n) {
            $a = $n->announcement;
            $official = ! $a || $a->isOfficial();

            return [
                'id' => $n->notification_id,
                'title' => $n->title(),
                'excerpt' => $a ? Str::limit(preg_replace('/\s+/', ' ', $a->body), 90) : '',
                'time' => $n->created_at?->diffForHumans(),
                'read' => (bool) $n->is_read || $n->isExpired(),
                'official' => $official,
                'priority' => $a->priority ?? 'normal',
                'alert' => $n->isAlerting(),
                'expired' => $n->isExpired(),
                'sender' => $official ? 'ZU Administration' : ($a->author->name ?? 'Instructor'),
                'course' => ! $official ? ($a->offering->title ?? null) : null,
                'url' => route('notifications.show', $n->notification_id),
            ];
        })->all();

        return [
            'items' => $items,
            'unread' => static::unreadCountFor($userId),
            'alerts' => static::alertCountFor($userId),
            'level' => static::alertLevelFor($userId),
            'readUrl' => route('notifications.read', ['notificationId' => '__ID__']),
            'readAllUrl' => route('notifications.read-all'),
            'inboxUrl' => route('notifications.index'),
        ];
    }
}
