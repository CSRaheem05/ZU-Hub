<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CoursePageController;

Route::get('/', fn () => view('home'))->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/courses', [CoursePageController::class, 'index'])->name('courses.index');
    Route::get('/my-courses', [CoursePageController::class, 'myCoursesRedirect'])->name('courses.mine');
    Route::get('/student/my-courses', [CoursePageController::class, 'studentCourses'])->name('student.courses');
    Route::get('/instructor/my-courses', [CoursePageController::class, 'instructorCourses'])->name('instructor.courses');
    Route::get('/instructor/courses/{offeringId}/students', [CoursePageController::class, 'instructorStudents'])->name('instructor.students');
    Route::get('/management', [CoursePageController::class, 'management'])->name('management.index');
    // Keep /dashboard as a compatibility redirect for old links.
    Route::get('/dashboard', fn () => redirect()->route('home'))->name('dashboard');
});
