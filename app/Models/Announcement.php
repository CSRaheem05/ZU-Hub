<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Something an administrator or instructor writes. Publishing it fans out one row per
 * recipient into the existing `notifications` table (see App\Services\AnnouncementService).
 *
 * Lifecycle (`state()`):
 *   draft      saved, nobody notified
 *   scheduled  committed, but publish_at is still in the future (invisible to recipients)
 *   live       publish_at has passed and it has not expired
 *   expired    expires_at has passed (kept as history, no longer an active alert)
 */
class Announcement extends Model
{
    public const PRIORITIES = ['normal' => 'Normal', 'important' => 'Important', 'urgent' => 'Urgent'];

    public const AUDIENCES = [
        'all' => 'Everyone',
        'students' => 'Students only',
        'instructors' => 'Instructors only',
        'course' => 'One of my courses',
        'my_students' => 'All my students',
    ];

    public const STATES = ['live' => 'Live', 'scheduled' => 'Scheduled', 'draft' => 'Draft', 'expired' => 'Expired'];

    protected $table = 'announcements';
    protected $primaryKey = 'announcement_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'author_id', 'author_role', 'audience', 'offering_id',
        'title', 'body', 'priority', 'status', 'publish_at', 'expires_at', 'published_at',
    ];

    protected function casts(): array
    {
        return ['publish_at' => 'datetime', 'expires_at' => 'datetime', 'published_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (Announcement $announcement) {
            $announcement->announcement_id ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'announcement_id';
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id', 'user_id');
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class, 'offering_id', 'offering_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class, 'announcement_id', 'announcement_id');
    }

    /* ------------------------------------------------------------------ scheduling */

    /** SQL for "the moment it became/becomes visible" (publish_at, or published_at for older rows). */
    public const GO_LIVE_SQL = 'COALESCE(announcements.publish_at, announcements.published_at)';

    public function goesLiveAt(): ?Carbon
    {
        return $this->publish_at ?? $this->published_at;
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isScheduled(): bool
    {
        return $this->isPublished() && $this->goesLiveAt()?->isFuture() === true;
    }

    public function isExpired(): bool
    {
        return $this->isPublished() && ! $this->isScheduled() && $this->expires_at?->isPast() === true;
    }

    public function isLive(): bool
    {
        return $this->isPublished() && ! $this->isScheduled() && ! $this->isExpired();
    }

    public function state(): string
    {
        return match (true) {
            ! $this->isPublished() => 'draft',
            $this->isScheduled() => 'scheduled',
            $this->isExpired() => 'expired',
            default => 'live',
        };
    }

    /** Published announcements recipients can see right now. */
    public function scopeVisibleNow(Builder $query): Builder
    {
        return $query->where('announcements.status', 'published')
            ->whereRaw(self::GO_LIVE_SQL.' <= ?', [now()]);
    }

    /** Visible now and not expired: the only ones that may act as alerts. */
    public function scopeActiveNow(Builder $query): Builder
    {
        return $query->visibleNow()->where(function (Builder $q) {
            $q->whereNull('announcements.expires_at')->orWhere('announcements.expires_at', '>', now());
        });
    }

    public function scopeInState(Builder $query, string $state): Builder
    {
        return match ($state) {
            'draft' => $query->where('announcements.status', 'draft'),
            'scheduled' => $query->where('announcements.status', 'published')->whereRaw(self::GO_LIVE_SQL.' > ?', [now()]),
            'expired' => $query->visibleNow()->whereNotNull('announcements.expires_at')->where('announcements.expires_at', '<=', now()),
            'live' => $query->activeNow(),
            default => $query,
        };
    }

    /* ------------------------------------------------------------------ presentation */

    /** Official university announcement (written by an administrator). */
    public function isOfficial(): bool
    {
        return $this->author_role === 'manager';
    }

    /** Official + Important/Urgent: eligible for the automatic banner and the bell alert. */
    public function isAlertWorthy(): bool
    {
        return $this->isOfficial() && in_array($this->priority, ['important', 'urgent'], true);
    }

    public function audienceLabel(): string
    {
        return match ($this->audience) {
            'course' => $this->offering?->title ?? 'A course',
            default => self::AUDIENCES[$this->audience] ?? $this->audience,
        };
    }
}
