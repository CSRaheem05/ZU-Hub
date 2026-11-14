<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoursePageController extends Controller
{
    // =========================================
    // 1. AVAILABLE COURSES
    // =========================================

    public function index(Request $request)
    {
        $courses = DB::table('Course_Offerings as o')
            ->join('Courses as c', 'c.course_id', '=', 'o.course_id')
            ->join('Users as u', 'u.user_id', '=', 'o.instructor_id')
            ->where('o.is_active', 1)
            ->select(
                'o.offering_id',
                'o.title',
                'o.description',
                'o.duration_hours',
                'c.course_name',
                'u.name as instructor_name'
            )
            ->orderByDesc('o.created_at')
            ->get();

        $enrolledOfferingIds = collect();

        if (
            $request->user() &&
            $request->user()->role === 'student'
        ) {
            $enrolledOfferingIds = DB::table('Course_Members')
                ->where('user_id', $request->user()->user_id)
                ->pluck('offering_id');
        }

        return view('courses.index', compact(
            'courses',
            'enrolledOfferingIds'
        ));
    }

    // =========================================
    // 2. STUDENT: MY COURSES
    // =========================================

    public function studentCourses(Request $request)
    {
        abort_unless(
            $request->user() &&
            $request->user()->role === 'student',
            403
        );

        $courses = DB::table('Course_Members as m')
            ->join(
                'Course_Offerings as o',
                'o.offering_id',
                '=',
                'm.offering_id'
            )
            ->join(
                'Courses as c',
                'c.course_id',
                '=',
                'o.course_id'
            )
            ->join(
                'Users as u',
                'u.user_id',
                '=',
                'o.instructor_id'
            )
            ->where('m.user_id', $request->user()->user_id)
            ->select(
                'o.offering_id',
                'o.title',
                'o.description',
                'o.is_active',
                'c.course_name',
                'u.name as instructor_name'
            )
            ->orderByDesc('o.created_at')
            ->get();

        return view('student.my-courses', compact('courses'));
    }

    // =========================================
    // 3. STUDENT: ENROLL IN COURSE
    // =========================================

    public function enroll(Request $request, string $offeringId)
    {
        abort_unless(
            $request->user() &&
            $request->user()->role === 'student',
            403
        );

        $course = DB::table('Course_Offerings')
            ->where('offering_id', $offeringId)
            ->where('is_active', 1)
            ->first();

        abort_unless($course, 404);

        $alreadyEnrolled = DB::table('Course_Members')
            ->where('user_id', $request->user()->user_id)
            ->where('offering_id', $offeringId)
            ->exists();

        if ($alreadyEnrolled) {
            return redirect()
                ->route('courses.index')
                ->with(
                    'success',
                    'You are already enrolled in this course.'
                );
        }

        DB::table('Course_Members')->insertOrIgnore([
            'user_id' => $request->user()->user_id,
            'offering_id' => $offeringId
        ]);

        return redirect()
            ->route('student.courses')
            ->with(
                'success',
                'You enrolled successfully!'
            );
    }

    // =========================================
    // 4. INSTRUCTOR: MY COURSES
    // =========================================

    public function instructorCourses(Request $request)
    {
        abort_unless(
            $request->user() &&
            $request->user()->role === 'instructor',
            403
        );

        $courses = DB::table('Course_Offerings as o')
            ->join(
                'Courses as c',
                'c.course_id',
                '=',
                'o.course_id'
            )
            ->where('o.instructor_id', $request->user()->user_id)
            ->select(
                'o.offering_id',
                'o.title',
                'o.description',
                'o.is_active',
                'o.students_count',
                'o.duration_hours',
                'o.lecture_schedule',
                'c.course_name'
            )
            ->orderByDesc('o.created_at')
            ->get();

        return view('instructor.my-courses', compact('courses'));
    }

    // =========================================
    // 5. INSTRUCTOR: VIEW ENROLLED STUDENTS
    // =========================================

    public function instructorStudents(
        Request $request,
        string $offeringId
    ) {
        abort_unless(
            $request->user() &&
            $request->user()->role === 'instructor',
            403
        );

        $course = DB::table('Course_Offerings as o')
            ->where('o.offering_id', $offeringId)
            ->where('o.instructor_id', $request->user()->user_id)
            ->select(
                'o.offering_id',
                'o.title'
            )
            ->first();

        abort_unless($course, 404);

        $students = DB::table('Course_Members as m')
            ->join(
                'Users as u',
                'u.user_id',
                '=',
                'm.user_id'
            )
            ->where('m.offering_id', $offeringId)
            ->where('u.role', 'student')
            ->select(
                'u.user_id',
                'u.name',
                'u.email'
            )
            ->orderBy('u.name')
            ->get();

        return view('instructor.students', compact(
            'course',
            'students'
        ));
    }

    // =========================================
    // 6. REDIRECT MY COURSES BY ROLE
    // =========================================

    public function myCoursesRedirect(Request $request)
    {
        return match ($request->user()->role) {
            'student' => redirect()->route('student.courses'),
            'instructor' => redirect()->route('instructor.courses'),
            default => abort(403),
        };
    }

    // =========================================
    // 7. MANAGEMENT DASHBOARD
    // =========================================

    public function management(Request $request)
    {
        abort_unless(
            $request->user() &&
            $request->user()->role === 'manager',
            403
        );

        return view('management.index', [
            'userCount' => DB::table('Users')->count(),
            'courseCount' => DB::table('Course_Offerings')->count(),
        ]);
    }

    // =========================================
    // 8. STUDENT: OPEN ENROLLED COURSE
    // =========================================

    public function showStudentCourse(
        Request $request,
        string $offeringId
    ) {
        abort_unless(
            $request->user() &&
            $request->user()->role === 'student',
            403
        );

        $userId = $request->user()->user_id;

        // Only enrolled students can view the content.
        $enrolled = DB::table('Course_Members')
            ->where('user_id', $userId)
            ->where('offering_id', $offeringId)
            ->exists();

        abort_unless($enrolled, 403);

        // Get this instructor's specific course offering.
        $course = DB::table('Course_Offerings as o')
            ->join(
                'Courses as c',
                'c.course_id',
                '=',
                'o.course_id'
            )
            ->join(
                'Users as u',
                'u.user_id',
                '=',
                'o.instructor_id'
            )
            ->where('o.offering_id', $offeringId)
            ->select(
                'o.offering_id',
                'o.title',
                'o.description',
                'o.is_active',
                'o.lecture_schedule',
                'c.course_name',
                'u.name as instructor_name'
            )
            ->first();

        abort_unless($course, 404);

        // Retrieve lessons belonging to this offering only.
        $lessons = DB::table('Lessons')
            ->where('offering_id', $offeringId)
            ->select(
                'lesson_id',
                'lesson_title',
                'lesson_type',
                'lesson_order',
                'lesson_url',
                'live_url',
                'scheduled_at'
            )
            ->orderBy('lesson_order')
            ->orderBy('created_at')
            ->get();

        return view('student.course-content', compact(
            'course',
            'lessons'
        ));
    }

    // =========================================
    // 9. STUDENT: CANCEL ENROLLMENT
    // =========================================

    public function unenroll(
        Request $request,
        string $offeringId
    ) {
        abort_unless(
            $request->user() &&
            $request->user()->role === 'student',
            403
        );

        $userId = $request->user()->user_id;

        // Delete only this student's enrollment.
        $deleted = DB::table('Course_Members')
            ->where('user_id', $userId)
            ->where('offering_id', $offeringId)
            ->delete();

        if ($deleted === 0) {
            return redirect()
                ->route('student.courses')
                ->with(
                    'error',
                    'You are not enrolled in this course.'
                );
        }

        // The database trigger updates students_count.
        return redirect()
            ->route('student.courses')
            ->with(
                'success',
                'You have successfully left the course.'
            );
    }
}
