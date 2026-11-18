<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Who may receive what, and the publish fan-out into the `notifications` inbox.
 * All recipient rules live here so they are enforced on the backend, never in the form.
 */
class AnnouncementService
{
    /** Audiences each role is allowed to address. */
    public const ROLE_AUDIENCES = [
        'manager' => ['all', 'students', 'instructors'],
        'instructor' => ['course', 'my_students'],
    ];

    public const ROLE_PRIORITIES = [
        'manager' => ['normal', 'important', 'urgent'],
        'instructor' => ['normal', 'important'],
    ];

    public function audiencesFor(string $role): array
    {
        return array_intersect_key(Announcement::AUDIENCES, array_flip(self::ROLE_AUDIENCES[$role] ?? []));
    }

    public function prioritiesFor(string $role): array
    {
        return array_intersect_key(Announcement::PRIORITIES, array_flip(self::ROLE_PRIORITIES[$role] ?? []));
    }

    /** Approved courses an instructor can address (their own only), with enrolled-student counts. */
    public function offeringsFor(User $instructor): Collection
    {
        return DB::table('course_offerings as o')
            ->leftJoin('course_members as m', 'm.offering_id', '=', 'o.offering_id')
            ->where('o.instructor_id', $instructor->user_id)
            ->where('o.approval_status', 'approved')
            ->groupBy('o.offering_id', 'o.title')
            ->orderBy('o.title')
            ->get(['o.offering_id', 'o.title', DB::raw('COUNT(m.user_id) as students')]);
    }

    /** @return Collection<int, string> ids of the users who should receive the announcement */
    public function recipientIds(Announcement $announcement): Collection
    {
        if (! in_array($announcement->audience, self::ROLE_AUDIENCES[$announcement->author_role] ?? [], true)) {
            throw new InvalidArgumentException("A {$announcement->author_role} cannot address the '{$announcement->audience}' audience.");
        }

        $ids = match ($announcement->audience) {
            // Administrators manage announcements from their own page and have no inbox, so "everyone" is students + instructors.
            'all' => DB::table('users')->whereIn('role', ['student', 'instructor'])->pluck('user_id'),
            'students' => DB::table('users')->where('role', 'student')->pluck('user_id'),
            'instructors' => DB::table('users')->where('role', 'instructor')->pluck('user_id'),
            // Instructors reach only students enrolled in THEIR OWN offerings.
            'course' => $this->enrolledStudents()
                ->where('o.offering_id', $announcement->offering_id)
                ->where('o.instructor_id', $announcement->author_id)
                ->pluck('m.user_id'),
            'my_students' => $this->enrolledStudents()
                ->where('o.instructor_id', $announcement->author_id)
                ->pluck('m.user_id'),
        };

        return $ids->unique()->reject(fn ($id) => $id === $announcement->author_id)->values();
    }

    /**
     * Commit an announcement: fan it out to every recipient's inbox, once.
     *
     * The go-live time is the announcement's `publish_at` (when it is still in the future the
     * announcement is "scheduled": recipients' rows exist, but every backend query hides them
     * until that moment, so no scheduler/cron is needed). Without a future `publish_at` it goes live now.
     *
     * Returns the number of recipients, or null when it was already published.
     */
    public function publish(Announcement $announcement): ?int
    {
        return DB::transaction(function () use ($announcement) {
            $locked = Announcement::query()->whereKey($announcement->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->isPublished()) {
                return null;
            }

            $now = now();
            $goLive = $locked->publish_at && $locked->publish_at->isFuture() ? $locked->publish_at : $now;
            $recipients = $this->recipientIds($locked);

            foreach ($recipients->chunk(500) as $chunk) {
                DB::table('notifications')->insertOrIgnore($chunk->map(fn ($userId) => [
                    'notification_id' => (string) Str::uuid(),
                    'user_id' => $userId,
                    'offering_id' => null,
                    'announcement_id' => $locked->announcement_id,
                    'message' => $locked->title,
                    'is_read' => 0,
                    'created_at' => $goLive, // timestamp shown to users = when it went live
                ])->all());
            }

            $locked->forceFill(['status' => 'published', 'published_at' => $now, 'publish_at' => $goLive])->save();
            $announcement->setRawAttributes($locked->getAttributes(), true);

            return $recipients->count();
        });
    }

    /** Move the go-live time of an announcement that has not gone live yet; keeps recipients' timestamps in sync. */
    public function reschedule(Announcement $announcement, ?Carbon $goLive): void
    {
        DB::transaction(function () use ($announcement, $goLive) {
            $goLive = $goLive && $goLive->isFuture() ? $goLive : now();
            $announcement->forceFill(['publish_at' => $goLive])->save();
            DB::table('notifications')->where('announcement_id', $announcement->announcement_id)->update(['created_at' => $goLive]);
        });
    }

    private function enrolledStudents()
    {
        return DB::table('course_members as m')
            ->join('course_offerings as o', 'o.offering_id', '=', 'm.offering_id')
            ->distinct();
    }
}
