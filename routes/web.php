
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CoursePageController;
use App\Http\Controllers\InstructorOfferingController;
use App\Http\Controllers\InstructorCourseManagementController;

Route::get('/', fn () => view('home'))->name('home');

Route::middleware('guest')->group(function () {

    Route::get('/login', [LoginController::class, 'create'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::middleware('auth')->group(function () {

    Route::post('/logout', [LoginController::class, 'destroy'])
        ->name('logout');

    Route::get('/courses', [CoursePageController::class, 'index'])
        ->name('courses.index');

    Route::get('/my-courses', [CoursePageController::class, 'myCoursesRedirect'])
        ->name('courses.mine');

    Route::get('/student/my-courses', [CoursePageController::class, 'studentCourses'])
        ->name('student.courses');

    Route::get('/instructor/my-courses', [CoursePageController::class, 'instructorCourses'])
        ->name('instructor.courses');

    Route::get('/instructor/courses/{offeringId}/students', [CoursePageController::class, 'instructorStudents'])
        ->name('instructor.students');

    Route::get('/management', [CoursePageController::class, 'management'])
        ->name('management.index');


    // ========================================
    // INSTRUCTOR: CREATE COURSE
    // ========================================

    Route::get('/instructor/courses/create', [InstructorOfferingController::class, 'create'])
        ->name('instructor.courses.create');

    Route::post('/instructor/courses', [InstructorOfferingController::class, 'store'])
        ->name('instructor.courses.store');


    // ========================================
    // INSTRUCTOR: EDIT COURSE
    // ========================================

    Route::get('/instructor/courses/{offeringId}/edit', [InstructorOfferingController::class, 'edit'])
        ->name('instructor.courses.edit');

    Route::put('/instructor/courses/{offeringId}', [InstructorOfferingController::class, 'update'])
        ->name('instructor.courses.update');


    // ========================================
    // INSTRUCTOR: ACTIVATE / DEACTIVATE
    // ========================================

    Route::patch('/instructor/courses/{offeringId}/status', [InstructorOfferingController::class, 'toggleStatus'])
        ->name('instructor.courses.status');


    // ========================================
    // INSTRUCTOR: DELETE COURSE
    // ========================================

    Route::delete('/instructor/courses/{offeringId}', [InstructorCourseManagementController::class, 'destroy'])
        ->name('instructor.courses.destroy');


    // ========================================
    // INSTRUCTOR: MANAGE LESSONS
    // ========================================

    // View all lessons
    Route::get('/instructor/courses/{offeringId}/lessons', [InstructorCourseManagementController::class, 'lessons'])
        ->name('instructor.lessons.index');

    // Add a lesson
    Route::post('/instructor/courses/{offeringId}/lessons', [InstructorCourseManagementController::class, 'storeLesson'])
        ->name('instructor.lessons.store');

    // Edit a lesson
    Route::put('/instructor/courses/{offeringId}/lessons/{lessonId}', [InstructorCourseManagementController::class, 'updateLesson'])
        ->name('instructor.lessons.update');

    // Delete a lesson
    Route::delete('/instructor/courses/{offeringId}/lessons/{lessonId}', [InstructorCourseManagementController::class, 'destroyLesson'])
        ->name('instructor.lessons.destroy');


    // ========================================
    // COMPATIBILITY REDIRECT
    // ========================================

    Route::get('/dashboard', fn () => redirect()->route('home'))
        ->name('dashboard');

});
