<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Course-content rules in one place: who may manage a course's content, how sections and the order of
 * everything are kept in MySQL, and who may open an uploaded file.
 *
 * Structure: a course has ordered SECTIONS (lesson_sections.section_order); a section has ordered LESSONS
 * (lessons.lesson_order counts inside its section). Students see sections in that order, lessons in that order.
 */
class LessonService
{
    public const TYPES = ['video', 'file', 'link', 'live'];
    public const DEFAULT_SECTION_TITLE = 'Getting Started';

    private static ?bool $available = null;

    /**
     * What a lesson is as far as instructors and students are concerned. An older "video" that was saved as a
     * link (a YouTube lesson, no uploaded file) is an external resource: it keeps playing exactly as before.
     */
    public static function kindOf(object $lesson): string
    {
        $type = (string) $lesson->lesson_type;

        if ($type === 'video' && empty($lesson->file_path ?? null) && ! empty($lesson->lesson_url ?? null)) {
            return 'link';
        }

        return $type;
    }

    /**
     * Sections need the lesson_sections table (a migration). Reading keeps working without it (everything is shown
     * as one flat list); only the instructor tools that depend on sections need it.
     */
    public function available(): bool
    {
        return self::$available ??= Schema::hasTable('lesson_sections') && Schema::hasColumn('lessons', 'section_id');
    }

    public static function forgetAvailability(): void
    {
        self::$available = null;
    }

    // ------------------------------------------------------------ ownership

    /**
     * The approved course this instructor owns. Anyone else (students, administrators, other
     * instructors) is turned away, so managing content stays with the owning instructor only.
     */
    public function ownedCourse(?User $user, string $offeringId): object
    {
        abort_unless($user && $user->role === 'instructor', 403);

        $course = DB::table('course_offerings')
            ->where('offering_id', $offeringId)
            ->where('instructor_id', $user->user_id)
            ->first();

        abort_unless($course, 404);
        abort_unless($course->approval_status === 'approved', 403, 'Course needs admin approval first.');

        return $course;
    }

    public function ownedLesson(?User $user, string $offeringId, string $lessonId): object
    {
        $this->ownedCourse($user, $offeringId);

        $lesson = DB::table('lessons')
            ->where('lesson_id', $lessonId)
            ->where('offering_id', $offeringId)
            ->first();

        abort_unless($lesson, 404);

        return $lesson;
    }

    public function ownedSection(?User $user, string $offeringId, string $sectionId): object
    {
        $this->ownedCourse($user, $offeringId);

        $section = DB::table('lesson_sections')
            ->where('section_id', $sectionId)
            ->where('offering_id', $offeringId)
            ->first();

        abort_unless($section, 404);

        return $section;
    }

    // ------------------------------------------------------------ reading

    /** Lessons of a course in the order students see them (ties broken the same way everywhere). */
    public function lessonsFor(string $offeringId): Collection
    {
        return DB::table('lessons')
            ->where('offering_id', $offeringId)
            ->orderBy('lesson_order')
            ->orderBy('created_at')
            ->orderBy('lesson_id')
            ->get();
    }

    /**
     * The whole course: ordered sections, each with its ordered lessons ($section->lessons).
     * Lessons without a section (or every lesson, when sections are not available yet) are shown in a
     * first, virtual section so nothing is ever hidden.
     */
    public function structure(string $offeringId): Collection
    {
        $lessons = DB::table('lessons')->where('offering_id', $offeringId)
            ->orderBy('lesson_order')->orderBy('created_at')->orderBy('lesson_id')->get();

        if (! $this->available()) {
            return $lessons->isEmpty() ? collect() : collect([$this->virtualSection($lessons)]);
        }

        $sections = DB::table('lesson_sections')->where('offering_id', $offeringId)
            ->orderBy('section_order')->orderBy('created_at')->orderBy('section_id')->get();

        $known = $sections->pluck('section_id')->all();
        $grouped = $lessons->groupBy(fn ($l) => in_array($l->section_id, $known, true) ? $l->section_id : '');

        $result = $sections->map(function ($section) use ($grouped) {
            $section->lessons = $grouped->get($section->section_id, collect())->values();

            return $section;
        });

        if ($grouped->has('')) {
            $result->prepend($this->virtualSection($grouped->get('')));
        }

        return $result->values();
    }

    private function virtualSection(Collection $lessons): object
    {
        return (object) ['section_id' => null, 'title' => self::DEFAULT_SECTION_TITLE, 'section_order' => 0, 'lessons' => $lessons->values()];
    }

    /** Ids of the lessons that students already have progress on (these cannot be deleted). */
    public function lessonsWithProgress(Collection $lessons): array
    {
        if ($lessons->isEmpty()) {
            return [];
        }

        return DB::table('progress')
            ->whereIn('lesson_id', $lessons->pluck('lesson_id'))
            ->distinct()
            ->pluck('lesson_id')
            ->all();
    }

    // ------------------------------------------------------------ locking and order helpers

    /** Serialises changes to one course's content (adding, moving, deleting). Call inside a transaction. */
    public function lockCourse(string $offeringId): void
    {
        DB::table('course_offerings')->where('offering_id', $offeringId)->lockForUpdate()->value('offering_id');
    }

    /** New content goes to the end of its section. Call inside a transaction, after lockCourse(). */
    public function nextOrder(string $offeringId, ?string $sectionId = null): int
    {
        $query = DB::table('lessons')->where('offering_id', $offeringId);

        if ($this->available()) {
            $sectionId === null ? $query->whereNull('section_id') : $query->where('section_id', $sectionId);
        }

        return ((int) $query->max('lesson_order')) + 1;
    }

    /** Closes the gaps in one section's order (1..n), keeping the current order. Call inside a transaction. */
    public function renumber(string $offeringId, string $sectionId): void
    {
        DB::table('lessons')->where('offering_id', $offeringId)->where('section_id', $sectionId)
            ->orderBy('lesson_order')->orderBy('created_at')->orderBy('lesson_id')->pluck('lesson_id')
            ->each(fn ($id, $i) => DB::table('lessons')->where('lesson_id', $id)->update(['lesson_order' => $i + 1]));
    }

    // ------------------------------------------------------------ sections

    /**
     * The section new content goes into. With no section chosen, the course's first section; a course with no
     * section yet gets "Getting Started" automatically. Lessons that somehow have no section are adopted into it.
     * Call inside a transaction, after lockCourse().
     */
    public function defaultSectionId(string $offeringId): string
    {
        $id = DB::table('lesson_sections')->where('offering_id', $offeringId)->orderBy('section_order')->orderBy('created_at')->value('section_id');

        if (! $id) {
            $id = $this->insertSection($offeringId, self::DEFAULT_SECTION_TITLE, 1);
        }

        DB::table('lessons')->where('offering_id', $offeringId)->whereNull('section_id')->update(['section_id' => $id]);

        return $id;
    }

    /** Is this section one of this course's? */
    public function sectionBelongs(string $offeringId, string $sectionId): bool
    {
        return DB::table('lesson_sections')->where('section_id', $sectionId)->where('offering_id', $offeringId)->exists();
    }

    public function createSection(string $offeringId, string $title): object
    {
        return DB::transaction(function () use ($offeringId, $title) {
            $this->lockCourse($offeringId);
            $order = ((int) DB::table('lesson_sections')->where('offering_id', $offeringId)->max('section_order')) + 1;
            $id = $this->insertSection($offeringId, $title, $order);

            return DB::table('lesson_sections')->where('section_id', $id)->first();
        });
    }

    private function insertSection(string $offeringId, string $title, int $order): string
    {
        $id = (string) Str::uuid();

        DB::table('lesson_sections')->insert([
            'section_id' => $id, 'offering_id' => $offeringId, 'title' => $title,
            'section_order' => $order, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }

    public function renameSection(string $offeringId, string $sectionId, string $title): void
    {
        DB::table('lesson_sections')->where('section_id', $sectionId)->where('offering_id', $offeringId)
            ->update(['title' => $title, 'updated_at' => now()]);
    }

    /**
     * Deletes a section, but only an EMPTY one: a section that still holds content is refused with a clear message,
     * so a lesson (and its student progress or uploaded file) can never disappear with its section.
     */
    public function deleteSection(string $offeringId, string $sectionId): void
    {
        DB::transaction(function () use ($offeringId, $sectionId) {
            $this->lockCourse($offeringId);

            $items = DB::table('lessons')->where('section_id', $sectionId)->where('offering_id', $offeringId)->count();

            if ($items > 0) {
                throw ValidationException::withMessages([
                    'section' => $items === 1
                        ? 'This section still has 1 item. Move or remove it first.'
                        : "This section still has {$items} items. Move or remove them first.",
                ]);
            }

            DB::table('lesson_sections')->where('section_id', $sectionId)->where('offering_id', $offeringId)->delete();

            // keep the order of the remaining sections a clean 1..n
            DB::table('lesson_sections')->where('offering_id', $offeringId)->orderBy('section_order')->orderBy('created_at')->pluck('section_id')
                ->each(fn ($id, $i) => DB::table('lesson_sections')->where('section_id', $id)->update(['section_order' => $i + 1]));
        });
    }

    // ------------------------------------------------------------ the layout (every drag, move and keyboard action)

    /**
     * Saves the whole structure in one transaction: the order of the sections, which section each lesson belongs to,
     * and the order of the lessons inside each section. $layout is [['id' => sectionId, 'lessons' => [lessonId, ...]], ...].
     *
     *  - a section or lesson id that does not belong to THIS course, or an id that is repeated, is rejected, so
     *    nothing can ever be moved between courses;
     *  - a section missing from $layout (created in another tab a moment ago) keeps its place after the others;
     *  - a lesson missing from $layout keeps its current section, after the listed lessons;
     *  - the saved order is always a clean 1..n, and lesson ids, files and progress are never touched.
     */
    public function applyLayout(string $offeringId, array $layout): void
    {
        DB::transaction(function () use ($offeringId, $layout) {
            $this->lockCourse($offeringId);

            $sections = DB::table('lesson_sections')->where('offering_id', $offeringId)->orderBy('section_order')->orderBy('created_at')->pluck('section_id')->all();
            $lessons = $this->lessonsFor($offeringId);
            $allLessons = $lessons->pluck('lesson_id')->all();
            $fallback = $sections[0] ?? null;

            $sectionIds = array_column($layout, 'id');
            $lessonIds = array_merge(...array_map(fn ($s) => array_values($s['lessons'] ?? []), $layout ?: [[]]));

            if (count($sectionIds) !== count(array_unique($sectionIds)) || array_diff($sectionIds, $sections)
                || count($lessonIds) !== count(array_unique($lessonIds)) || array_diff($lessonIds, $allLessons)) {
                throw ValidationException::withMessages(['layout' => 'The list changed. Refresh the page and try again.']);
            }

            // final section order: the layout's, then any section it did not mention
            $order = array_merge($sectionIds, array_values(array_diff($sections, $sectionIds)));
            $members = array_fill_keys($order, []);

            foreach ($layout as $section) {
                $members[$section['id']] = array_values($section['lessons'] ?? []);
            }

            // lessons not mentioned stay in their current section (or the first one), after the listed ones
            $placed = array_flip($lessonIds);
            foreach ($lessons as $lesson) {
                if (isset($placed[$lesson->lesson_id])) {
                    continue;
                }
                $target = in_array($lesson->section_id, $order, true) ? $lesson->section_id : ($fallback ?? $order[0] ?? null);
                if ($target !== null) {
                    $members[$target][] = $lesson->lesson_id;
                }
            }

            foreach ($order as $index => $sectionId) {
                DB::table('lesson_sections')->where('section_id', $sectionId)->where('offering_id', $offeringId)
                    ->update(['section_order' => $index + 1, 'updated_at' => now()]);

                foreach ($members[$sectionId] as $position => $lessonId) {
                    DB::table('lessons')->where('lesson_id', $lessonId)->where('offering_id', $offeringId)
                        ->update(['section_id' => $sectionId, 'lesson_order' => $position + 1]);
                }
            }
        });
    }

    // ------------------------------------------------------------ file access

    /**
     * May this user open a lesson's uploaded file (document or video)?
     *  - the course's own instructor, always;
     *  - a student, once the course is approved, and either enrolled (ongoing course) or the
     *    course is completed (open to everyone, no enrolment needed). Same rule as the course page;
     *  - nobody else, administrators included.
     */
    public function canOpenFile(?User $user, object $course): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->role === 'instructor') {
            return $course->instructor_id === $user->user_id;
        }

        if ($user->role !== 'student' || $course->approval_status !== 'approved') {
            return false;
        }

        return $course->course_status === 'completed'
            || DB::table('course_members')->where('user_id', $user->user_id)->where('offering_id', $course->offering_id)->exists();
    }
}
