<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InstructorCourseManagementController extends Controller
{
    private function ownOffering(Request $request, string $id)
    {
        abort_unless(
            $request->user() &&
            $request->user()->role === 'instructor',
            403
        );

        $offering = DB::table('Course_Offerings')
            ->where('offering_id', $id)
            ->where('instructor_id', $request->user()->user_id)
            ->first();

        abort_unless($offering, 404);

        return $offering;
    }

    private function ownLesson(
        Request $request,
        string $offeringId,
        string $lessonId
    ) {
        $this->ownOffering($request, $offeringId);

        $lesson = DB::table('Lessons')
            ->where('lesson_id', $lessonId)
            ->where('offering_id', $offeringId)
            ->first();

        abort_unless($lesson, 404);

        return $lesson;
    }

    public function destroy(Request $request, string $offeringId)
    {
        $this->ownOffering($request, $offeringId);

        return DB::transaction(function () use ($request, $offeringId) {
            $course = DB::table('Course_Offerings')
                ->where('offering_id', $offeringId)
                ->where('instructor_id', $request->user()->user_id)
                ->lockForUpdate()
                ->first();

            abort_unless($course, 404);

            $hasStudents = DB::table('Course_Members')
                ->where('offering_id', $offeringId)
                ->exists();

            $hasLessons = DB::table('Lessons')
                ->where('offering_id', $offeringId)
                ->exists();

            $hasReviews = DB::table('Reviews')
                ->where('offering_id', $offeringId)
                ->exists();

            $hasNotifications = DB::table('Notifications')
                ->where('offering_id', $offeringId)
                ->exists();

            if (
                $hasStudents ||
                $hasLessons ||
                $hasReviews ||
                $hasNotifications
            ) {
                return redirect()
                    ->route('instructor.courses')
                    ->with(
                        'error',
                        'This course has related data. Use Deactivate instead.'
                    );
            }

            DB::table('Course_Offerings')
                ->where('offering_id', $offeringId)
                ->where('instructor_id', $request->user()->user_id)
                ->delete();

            return redirect()
                ->route('instructor.courses')
                ->with('success', 'Course deleted successfully.');
        });
    }

    public function lessons(Request $request, string $offeringId)
    {
        $course = $this->ownOffering($request, $offeringId);

        $lessons = DB::table('Lessons')
            ->where('offering_id', $offeringId)
            ->orderBy('lesson_order')
            ->orderBy('created_at')
            ->get();

        return view('instructor.manage-lessons', compact(
            'course',
            'lessons'
        ));
    }

    private function validateLesson(Request $request): array
    {
        return $request->validate([
            'lesson_title' => ['required', 'string', 'max:255'],

            'lesson_type' => [
                'required',
                Rule::in(['video', 'link', 'live'])
            ],

            'lesson_order' => ['required', 'integer', 'min:0'],

            'lesson_url' => [
                Rule::requiredIf(
                    in_array(
                        $request->input('lesson_type'),
                        ['video', 'link'],
                        true
                    )
                ),
                'nullable',
                'url:http,https'
            ],

            'live_url' => [
                Rule::requiredIf(
                    $request->input('lesson_type') === 'live'
                ),
                'nullable',
                'url:http,https'
            ],

            'scheduled_at' => [
                Rule::requiredIf(
                    $request->input('lesson_type') === 'live'
                ),
                'nullable',
                'date'
            ]
        ]);
    }

    private function lessonValues(array $data): array
    {
        $type = $data['lesson_type'];

        return [
            'lesson_title' => $data['lesson_title'],
            'lesson_type' => $type,
            'lesson_order' => $data['lesson_order'],

            'lesson_url' => in_array($type, ['video', 'link'], true)
                ? ($data['lesson_url'] ?? null)
                : null,

            'live_url' => $type === 'live'
                ? ($data['live_url'] ?? null)
                : null,

            'scheduled_at' => $type === 'live'
                ? ($data['scheduled_at'] ?? null)
                : null
        ];
    }

    public function storeLesson(
        Request $request,
        string $offeringId
    ) {
        $this->ownOffering($request, $offeringId);

        $data = $this->validateLesson($request);

        DB::table('Lessons')->insert(
            array_merge(
                [
                    'lesson_id' => (string) Str::uuid(),
                    'offering_id' => $offeringId,
                    'created_at' => now()
                ],
                $this->lessonValues($data)
            )
        );

        return redirect()
            ->route('instructor.lessons.index', $offeringId)
            ->with('success', 'Lesson added successfully.');
    }

    public function updateLesson(
        Request $request,
        string $offeringId,
        string $lessonId
    ) {
        $this->ownLesson($request, $offeringId, $lessonId);

        $data = $this->validateLesson($request);

        DB::table('Lessons')
            ->where('lesson_id', $lessonId)
            ->where('offering_id', $offeringId)
            ->update($this->lessonValues($data));

        return redirect()
            ->route('instructor.lessons.index', $offeringId)
            ->with('success', 'Lesson updated successfully.');
    }

    public function destroyLesson(
        Request $request,
        string $offeringId,
        string $lessonId
    ) {
        $this->ownLesson($request, $offeringId, $lessonId);

        $hasProgress = DB::table('Progress')
            ->where('lesson_id', $lessonId)
            ->exists();

        if ($hasProgress) {
            return redirect()
                ->route('instructor.lessons.index', $offeringId)
                ->with(
                    'error',
                    'This lesson has student progress and cannot be deleted.'
                );
        }

        DB::table('Lessons')
            ->where('lesson_id', $lessonId)
            ->where('offering_id', $offeringId)
            ->delete();

        return redirect()
            ->route('instructor.lessons.index', $offeringId)
            ->with('success', 'Lesson deleted successfully.');
    }
}
