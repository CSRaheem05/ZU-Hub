<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CoursePageController;
use App\Http\Controllers\InstructorOfferingController;
use App\Http\Controllers\InstructorCourseManagementController;


// ========================================
// HOME PAGE
// ========================================

Route::get('/', fn () => view('home'))->name('home');





// ========================================
// GUEST ROUTES
// ========================================

Route::middleware('guest')->group(function () {

    Route::get('/login', [LoginController::class, 'create'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');

});


// ========================================
// AUTHENTICATED ROUTES
// ========================================

Route::middleware('auth')->group(function () {

    // LOGOUT
    Route::post('/logout', [LoginController::class, 'destroy'])
        ->name('logout');


    // ========================================
    // AVAILABLE COURSES
    // ========================================

    Route::get('/courses', [CoursePageController::class, 'index'])
        ->name('courses.index');


    // ========================================
    // STUDENT: ENROLL IN COURSE (NEW)
    // ========================================

    Route::post(
        '/courses/{offeringId}/enroll',
        [CoursePageController::class, 'enroll']
    )->name('courses.enroll');


    // ========================================
    // MY COURSES REDIRECT
    // ========================================

    Route::get('/my-courses', [CoursePageController::class, 'myCoursesRedirect'])
        ->name('courses.mine');


    // ========================================
    // STUDENT: MY COURSES
    // ========================================

    Route::get('/student/my-courses', [CoursePageController::class, 'studentCourses'])
        ->name('student.courses');


    // ========================================
    // INSTRUCTOR: MY COURSES
    // ========================================

    Route::get('/instructor/my-courses', [CoursePageController::class, 'instructorCourses'])
        ->name('instructor.courses');


    // ========================================
    // INSTRUCTOR: VIEW STUDENTS
    // ========================================

    Route::get(
        '/instructor/courses/{offeringId}/students',
        [CoursePageController::class, 'instructorStudents']
    )->name('instructor.students');


    // ========================================
    // MANAGEMENT
    // ========================================

    Route::get('/management', [CoursePageController::class, 'management'])
        ->name('management.index');


    // ========================================
    // INSTRUCTOR: CREATE COURSE
    // ========================================

    Route::get(
        '/instructor/courses/create',
        [InstructorOfferingController::class, 'create']
    )->name('instructor.courses.create');

    Route::post(
        '/instructor/courses',
        [InstructorOfferingController::class, 'store']
    )->name('instructor.courses.store');


    // ========================================
    // INSTRUCTOR: EDIT COURSE
    // ========================================

    Route::get(
        '/instructor/courses/{offeringId}/edit',
        [InstructorOfferingController::class, 'edit']
    )->name('instructor.courses.edit');

    Route::put(
        '/instructor/courses/{offeringId}',
        [InstructorOfferingController::class, 'update']
    )->name('instructor.courses.update');


    // ========================================
    // INSTRUCTOR: ACTIVATE / DEACTIVATE
    // ========================================

    Route::patch(
        '/instructor/courses/{offeringId}/status',
        [InstructorOfferingController::class, 'toggleStatus']
    )->name('instructor.courses.status');


    // ========================================
    // INSTRUCTOR: DELETE COURSE
    // ========================================

    Route::delete(
        '/instructor/courses/{offeringId}',
        [InstructorCourseManagementController::class, 'destroy']
    )->name('instructor.courses.destroy');


    // ========================================
    // INSTRUCTOR: MANAGE LESSONS
    // ========================================

    // View lessons
    Route::get(
        '/instructor/courses/{offeringId}/lessons',
        [InstructorCourseManagementController::class, 'lessons']
    )->name('instructor.lessons.index');

    // Add lesson
    Route::post(
        '/instructor/courses/{offeringId}/lessons',
        [InstructorCourseManagementController::class, 'storeLesson']
    )->name('instructor.lessons.store');

    // Update lesson
    Route::put(
        '/instructor/courses/{offeringId}/lessons/{lessonId}',
        [InstructorCourseManagementController::class, 'updateLesson']
    )->name('instructor.lessons.update');

    // Delete lesson
    Route::delete(
        '/instructor/courses/{offeringId}/lessons/{lessonId}',
        [InstructorCourseManagementController::class, 'destroyLesson']
    )->name('instructor.lessons.destroy');


    // ========================================
    // COMPATIBILITY REDIRECT
    // ========================================

    Route::get('/dashboard', fn () => redirect()->route('home'))
        ->name('dashboard');



    // ========================================
    // STUDENT: VIEW COURSE CONTENT
    // ========================================

    Route::get(
        '/student/courses/{offeringId}',
        [CoursePageController::class, 'showStudentCourse']
    )->name('student.course.show');


    // ========================================
    // STUDENT: CANCEL ENROLLMENT
    // ========================================

    Route::delete(
        '/student/courses/{offeringId}/unenroll',
        [CoursePageController::class, 'unenroll']
    )->name('student.course.unenroll');


});
