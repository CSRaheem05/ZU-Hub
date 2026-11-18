<?php

namespace App\Http\Controllers;

use App\Http\Requests\LessonRequest;
use App\Services\LessonFileService;
use App\Services\LessonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * An instructor's course content: videos (uploaded MP4 or link), uploaded materials, external links and
 * Microsoft Teams sessions, grouped into sections.
 *
 * Every action is limited to the instructor who owns the (approved) course. Students, other
 * instructors and administrators cannot list, add, edit, move, delete or preview content here.
 */
class InstructorLessonController extends Controller
{
    public function __construct(private LessonService $lessons, private LessonFileService $files)
    {
    }

    public function index(Request $request, string $offeringId)
    {
        $course = $this->lessons->ownedCourse($request->user(), $offeringId);

        // Sections come from a migration. Until it has run, say so instead of failing.
        if (! $this->lessons->available()) {
            return view('instructor.manage-lessons', ['course' => $course, 'pending' => true, 'sections' => collect(), 'locked' => [], 'files' => $this->files]);
        }

        // Self-heal: a lesson without a section (for example after an interrupted change) is adopted by the first section.
        if (DB::table('lessons')->where('offering_id', $offeringId)->whereNull('section_id')->exists()) {
            DB::transaction(function () use ($offeringId) {
                $this->lessons->lockCourse($offeringId);
                $this->lessons->defaultSectionId($offeringId);
            });
        }

        $sections = $this->lessons->structure($offeringId);

        return view('instructor.manage-lessons', [
            'course' => $course,
            'pending' => false,
            'sections' => $sections,
            'locked' => $this->lessons->lessonsWithProgress($sections->pluck('lessons')->flatten(1)),
            'files' => $this->files,
            'documentLimit' => $this->files->effectiveMaxBytes(LessonFileService::DOCUMENT),
            'videoLimit' => $this->files->effectiveMaxBytes(LessonFileService::VIDEO),
        ]);
    }

    /** Exactly what students see, without needing to be enrolled. Read-only, for the owner. */
    public function preview(Request $request, string $offeringId)
    {
        $course = $this->lessons->ownedCourse($request->user(), $offeringId);

        $course = DB::table('course_offerings as o')
            ->join('courses as c', 'c.course_id', '=', 'o.course_id')
            ->join('users as u', 'u.user_id', '=', 'o.instructor_id')
            ->where('o.offering_id', $course->offering_id)
            ->select('o.*', 'c.course_name', 'u.name as instructor_name')
            ->first();

        return view('student.course-content', [
            'course' => $course,
            'sections' => $this->lessons->structure($offeringId),
            'preview' => true,
        ]);
    }

    public function store(LessonRequest $request, string $offeringId): JsonResponse|RedirectResponse
    {
        $values = $request->values();
        $type = $request->contentType();
        $hasFile = in_array($type, ['file', 'video'], true);
        $stored = null;
        $id = (string) Str::uuid();

        try {
            DB::transaction(function () use ($request, $offeringId, $values, $type, $hasFile, $id, &$stored) {
                $this->lessons->lockCourse($offeringId);

                // content added before any section exists goes into an automatic "Getting Started" section
                $sectionId = $request->input('section_id') ?: $this->lessons->defaultSectionId($offeringId);

                $row = array_merge($values, [
                    'lesson_id' => $id,
                    'offering_id' => $offeringId,
                    'section_id' => $sectionId,
                    'lesson_type' => $type,
                    'lesson_order' => $this->lessons->nextOrder($offeringId, $sectionId),   // always the end of its section
                    'created_at' => now(),
                ]);

                if ($hasFile) {
                    $stored = $this->files->store($request->file('file'), $request->filePurpose());
                    $row = array_merge($row, $stored);
                }

                DB::table('lessons')->insert($row);
            });
        } catch (Throwable $e) {
            if ($stored) {
                $this->files->delete($stored['file_path']);   // nothing was saved, so do not keep the file
            }

            throw $e;
        }

        return $this->done($request, $offeringId, 'Content added.', $id, 201);
    }

    public function update(LessonRequest $request, string $offeringId, string $lessonId): JsonResponse|RedirectResponse
    {
        $lesson = $request->lesson;
        $values = $request->values();
        $kind = $request->contentType();      // a saved YouTube "video" is an external resource: it has no file
        $stored = null;
        $oldPath = null;
        $oldSection = null;

        try {
            DB::transaction(function () use ($request, $offeringId, $lessonId, $lesson, $kind, &$values, &$stored, &$oldPath, &$oldSection) {
                $this->lessons->lockCourse($offeringId);

                if (in_array($kind, ['file', 'video'], true) && $request->hasFile('file')) {
                    $stored = $this->files->store($request->file('file'), $request->filePurpose());
                    $values = array_merge($values, $stored);
                    $oldPath = $lesson->file_path;      // the replaced file is removed only after the new record is saved
                }

                // moved to another section by the "Section" field: it goes to the end of that section
                $target = $request->input('section_id');
                if ($target && $target !== $lesson->section_id) {
                    $values['section_id'] = $target;
                    $values['lesson_order'] = $this->lessons->nextOrder($offeringId, $target);
                    $oldSection = $lesson->section_id;
                }

                DB::table('lessons')->where('lesson_id', $lessonId)->where('offering_id', $offeringId)->update($values);

                if ($oldSection) {
                    $this->lessons->renumber($offeringId, $oldSection);
                }
            });
        } catch (Throwable $e) {
            if ($stored) {
                $this->files->delete($stored['file_path']);
            }

            throw $e;
        }

        // the old file is only removed once the new record is safely saved
        $this->files->delete($oldPath);

        return $this->done($request, $offeringId, 'Changes saved.', $lessonId);
    }

    public function destroy(Request $request, string $offeringId, string $lessonId): RedirectResponse
    {
        $lesson = $this->lessons->ownedLesson($request->user(), $offeringId, $lessonId);

        $deleted = DB::transaction(function () use ($offeringId, $lessonId, $lesson) {
            $this->lessons->lockCourse($offeringId);

            // Students who started this lesson keep their progress: it cannot be deleted.
            if (DB::table('progress')->where('lesson_id', $lessonId)->exists()) {
                return false;
            }

            $gone = DB::table('lessons')->where('lesson_id', $lessonId)->where('offering_id', $offeringId)->delete() > 0;

            if ($gone && ($lesson->section_id ?? null)) {
                $this->lessons->renumber($offeringId, $lesson->section_id);
            }

            return $gone;
        });

        if (! $deleted) {
            return redirect()
                ->route('instructor.lessons.index', $offeringId)
                ->with('error', 'This lesson has student progress and cannot be deleted.');
        }

        // The row is gone for good; now remove its file (a failure is logged and never shown as an error).
        $this->files->delete($lesson->file_path);

        return redirect()
            ->route('instructor.lessons.index', $offeringId)
            ->with('success', 'Content deleted.');
    }

    /**
     * Saves the whole structure after any drag, move or keyboard action: the order of the sections, the section each
     * lesson belongs to and the order inside each section, in one transaction (see LessonService::applyLayout).
     */
    public function layout(Request $request, string $offeringId): JsonResponse
    {
        $this->lessons->ownedCourse($request->user(), $offeringId);
        abort_unless($this->lessons->available(), 409, 'Course sections are not set up yet.');

        $data = $request->validate([
            'sections' => ['required', 'array', 'min:1', 'max:200'],
            'sections.*.id' => ['required', 'string', 'max:50'],
            'sections.*.lessons' => ['present', 'array', 'max:1000'],
            'sections.*.lessons.*' => ['required', 'string', 'max:50'],
        ]);

        $this->lessons->applyLayout($offeringId, array_values($data['sections']));

        return response()->json(['ok' => true]);
    }

    /** The page submits with fetch (JSON); a plain form post still works and redirects. */
    private function done(Request $request, string $offeringId, string $message, string $lessonId, int $status = 200): JsonResponse|RedirectResponse
    {
        $url = route('instructor.lessons.index', $offeringId).'#lesson-'.$lessonId;

        if ($request->expectsJson()) {
            session()->flash('success', $message);

            return response()->json(['redirect' => $url], $status);
        }

        return redirect($url)->with('success', $message);
    }
}
