<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoursePageController extends Controller
{
    public function index()
    {
        $courses = DB::table('Course_Offerings as o')
            ->join('Courses as c', 'c.course_id', '=', 'o.course_id')
            ->join('Users as u', 'u.user_id', '=', 'o.instructor_id')
            ->where('o.is_active', 1)
            ->select('o.offering_id', 'o.title', 'o.description', 'o.duration_hours', 'c.course_name', 'u.name as instructor_name')
            ->orderByDesc('o.created_at')->get();
        return view('courses.index', compact('courses'));
    }

    // Only courses the logged-in student has actually joined.
    public function studentCourses(Request $request)
    {
        abort_unless($request->user()->role === 'student', 403);

        $courses = DB::table('Course_Members as m')
            ->join('Course_Offerings as o', 'o.offering_id', '=', 'm.offering_id')
            ->join('Courses as c', 'c.course_id', '=', 'o.course_id')
            ->join('Users as u', 'u.user_id', '=', 'o.instructor_id')
            ->where('m.user_id', $request->user()->user_id)
            ->select('o.offering_id', 'o.title', 'o.description', 'o.is_active',
                     'c.course_name', 'u.name as instructor_name')
            ->orderByDesc('o.created_at')
            ->get();

        return view('student.my-courses', compact('courses'));
    }

    // Only course offerings assigned to the logged-in instructor.
    public function instructorCourses(Request $request)
    {
        abort_unless($request->user()->role === 'instructor', 403);

        $courses = DB::table('Course_Offerings as o')
            ->join('Courses as c', 'c.course_id', '=', 'o.course_id')
            ->where('o.instructor_id', $request->user()->user_id)
            ->select('o.offering_id', 'o.title', 'o.description', 'o.is_active',
                     'o.students_count', 'c.course_name')
            ->orderByDesc('o.created_at')
            ->get();

        return view('instructor.my-courses', compact('courses'));
    }

    // Instructor can see enrolled students only in their own offering.
    public function instructorStudents(Request $request, string $offeringId)
    {
        abort_unless($request->user()->role === 'instructor', 403);

        $course = DB::table('Course_Offerings as o')
            ->where('o.offering_id', $offeringId)
            ->where('o.instructor_id', $request->user()->user_id)
            ->select('o.offering_id', 'o.title')
            ->first();
        abort_unless($course, 404);

        $students = DB::table('Course_Members as m')
            ->join('Users as u', 'u.user_id', '=', 'm.user_id')
            ->where('m.offering_id', $offeringId)
            ->where('u.role', 'student')
            ->select('u.user_id', 'u.name', 'u.email')
            ->orderBy('u.name')
            ->get();

        return view('instructor.students', compact('course', 'students'));
    }

    // Keep old bookmarks working without allowing access to the wrong role.
    public function myCoursesRedirect(Request $request)
    {
        return match ($request->user()->role) {
            'student' => redirect()->route('student.courses'),
            'instructor' => redirect()->route('instructor.courses'),
            default => abort(403),
        };
    }

    public function management(Request $request)
    {
        abort_unless($request->user()->role === 'manager', 403);
        return view('management.index', [
          'userCount'=>DB::table('Users')->count(),
          'courseCount'=>DB::table('Course_Offerings')->count(),
        ]);
    }
}
