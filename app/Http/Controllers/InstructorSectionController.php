<?php

namespace App\Http\Controllers;

use App\Http\Requests\SectionRequest;
use App\Services\LessonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Course sections: create, rename and delete (empty sections only). Reordering sections, and moving content
 * between them, is saved through InstructorLessonController::layout.
 *
 * Owner-only, like the lessons: students, other instructors and administrators are refused.
 */
class InstructorSectionController extends Controller
{
    public function __construct(private LessonService $lessons)
    {
    }

    public function store(SectionRequest $request, string $offeringId): JsonResponse|RedirectResponse
    {
        $section = $this->lessons->createSection($offeringId, $request->validated('title'));

        if ($request->expectsJson()) {
            return response()->json(['section' => ['id' => $section->section_id, 'title' => $section->title]], 201);
        }

        return redirect()->route('instructor.lessons.index', $offeringId)->with('success', 'Section added.');
    }

    public function update(SectionRequest $request, string $offeringId, string $sectionId): JsonResponse|RedirectResponse
    {
        $this->lessons->renameSection($offeringId, $sectionId, $request->validated('title'));

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'title' => $request->validated('title')]);
        }

        return redirect()->route('instructor.lessons.index', $offeringId)->with('success', 'Section renamed.');
    }

    /** Only an empty section can be deleted; a section that still has content is refused with a clear message. */
    public function destroy(Request $request, string $offeringId, string $sectionId): JsonResponse|RedirectResponse
    {
        $this->lessons->ownedSection($request->user(), $offeringId, $sectionId);
        abort_unless($this->lessons->available(), 409);

        $this->lessons->deleteSection($offeringId, $sectionId);   // throws a validation error (422) when it still has content

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('instructor.lessons.index', $offeringId)->with('success', 'Section deleted.');
    }
}
