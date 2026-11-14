<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InstructorOfferingController extends Controller
{
    private function checkInstructor(Request $request): void
    {
        abort_unless(
            $request->user() &&
            $request->user()->role === 'instructor',
            403
        );
    }

    private function ownCourse(Request $request, string $id)
    {
        $this->checkInstructor($request);

        $course = DB::table('Course_Offerings')
            ->where('offering_id', $id)
            ->where('instructor_id', $request->user()->user_id)
            ->first();

        abort_unless($course, 404);

        return $course;
    }

    public function create(Request $request)
    {
        $this->checkInstructor($request);

        $subjects = $this->getSubjects();

        return view('instructor.course-form', [
            'subjects' => $subjects,
            'course' => null
        ]);
    }

    private function getSubjects()
    {
        return DB::table('Courses as c')
            ->join(
                'departments as d',
                'd.department_code',
                '=',
                'c.department_code'
            )
            ->join(
                'colleges as co',
                'co.college_code',
                '=',
                'd.college_code'
            )
            ->select(
                'c.course_id',
                'c.course_name',
                'd.department_code',
                'd.department_name',
                'co.college_code',
                'co.college_name'
            )
            ->orderBy('co.college_name')
            ->orderBy('d.department_name')
            ->orderBy('c.course_name')
            ->get();
    }

    public function store(Request $request)
    {
        $this->checkInstructor($request);

        $data = $request->validate([
            'course_id' => [
                'required',
                Rule::exists('Courses', 'course_id')
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_hours' => [
                'nullable',
                'numeric',
                'min:0.01',
                'max:9999.99'
            ],
            'lecture_schedule' => ['nullable', 'string']
        ]);

        DB::table('Course_Offerings')->insert([
            'offering_id' => (string) Str::uuid(),
            'course_id' => $data['course_id'],
            'instructor_id' => $request->user()->user_id,
            'students_count' => 0,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'duration_hours' => $data['duration_hours'] ?? null,
            'lecture_schedule' => $data['lecture_schedule'] ?? null,
            'is_active' => 1,
            'created_at' => now()
        ]);

        return redirect()
            ->route('instructor.courses')
            ->with('success', 'Course created successfully.');
    }

    public function edit(Request $request, string $offeringId)
    {
        $course = $this->ownCourse($request, $offeringId);
        $subjects = $this->getSubjects();

        return view('instructor.course-form', compact(
            'course',
            'subjects'
        ));
    }

    public function update(
        Request $request,
        string $offeringId
    ) {
        $this->ownCourse($request, $offeringId);

        $data = $request->validate([
            'course_id' => [
                'required',
                Rule::exists('Courses', 'course_id')
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_hours' => [
                'nullable',
                'numeric',
                'min:0.01',
                'max:9999.99'
            ],
            'lecture_schedule' => ['nullable', 'string']
        ]);

        DB::table('Course_Offerings')
            ->where('offering_id', $offeringId)
            ->where('instructor_id', $request->user()->user_id)
            ->update([
                'course_id' => $data['course_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'duration_hours' => $data['duration_hours'] ?? null,
                'lecture_schedule' => $data['lecture_schedule'] ?? null
            ]);

        return redirect()
            ->route('instructor.courses')
            ->with('success', 'Course updated successfully.');
    }

    public function toggleStatus(
        Request $request,
        string $offeringId
    ) {
        $course = $this->ownCourse($request, $offeringId);

        DB::table('Course_Offerings')
            ->where('offering_id', $offeringId)
            ->where('instructor_id', $request->user()->user_id)
            ->update([
                'is_active' => $course->is_active ? 0 : 1
            ]);

        return redirect()
            ->route('instructor.courses')
            ->with('success', 'Course status updated.');
    }
}
