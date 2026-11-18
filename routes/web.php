<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CoursePageController;
use App\Http\Controllers\InstructorOfferingController;
use App\Http\Controllers\InstructorCourseManagementController;
use App\Http\Controllers\AdminCourseRequestController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\CourseCoverController;
use App\Http\Controllers\InstructorLessonController;
use App\Http\Controllers\InstructorSectionController;
use App\Http\Controllers\LessonFileController;


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
    // INSTRUCTOR: DELETE COURSE
    // ========================================

    Route::delete(
        '/instructor/courses/{offeringId}',
        [InstructorCourseManagementController::class, 'destroy']
    )->name('instructor.courses.destroy');


    // ========================================
    // INSTRUCTOR: COURSE CONTENT (owner only)
    // ========================================

    Route::get('/instructor/courses/{offeringId}/lessons', [InstructorLessonController::class, 'index'])->name('instructor.lessons.index');
    Route::get('/instructor/courses/{offeringId}/lessons/preview', [InstructorLessonController::class, 'preview'])->name('instructor.lessons.preview');
    Route::post('/instructor/courses/{offeringId}/lessons', [InstructorLessonController::class, 'store'])->name('instructor.lessons.store');
    // one call saves every drag / move / keyboard action: section order, lesson order and the section of each lesson
    Route::post('/instructor/courses/{offeringId}/lessons/layout', [InstructorLessonController::class, 'layout'])->name('instructor.lessons.layout');
    Route::put('/instructor/courses/{offeringId}/lessons/{lessonId}', [InstructorLessonController::class, 'update'])->name('instructor.lessons.update');
    Route::delete('/instructor/courses/{offeringId}/lessons/{lessonId}', [InstructorLessonController::class, 'destroy'])->name('instructor.lessons.destroy');

    // Sections (owner only)
    Route::post('/instructor/courses/{offeringId}/sections', [InstructorSectionController::class, 'store'])->name('instructor.sections.store');
    Route::put('/instructor/courses/{offeringId}/sections/{sectionId}', [InstructorSectionController::class, 'update'])->name('instructor.sections.update');
    Route::delete('/instructor/courses/{offeringId}/sections/{sectionId}', [InstructorSectionController::class, 'destroy'])->name('instructor.sections.destroy');

    // Uploaded materials: the owner, an enrolled student (ongoing course) or any student (completed course)
    Route::get('/lessons/{lessonId}/file', [LessonFileController::class, 'show'])->name('lessons.file');


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


    // Course approvals: request status stays within the instructor workspace.
    Route::get('/instructor/course-requests', [InstructorOfferingController::class,'requests'])->name('instructor.requests');
    Route::patch('/instructor/courses/{offeringId}/complete', [InstructorOfferingController::class,'complete'])->name('instructor.courses.complete');
    Route::get('/management/course-requests', [AdminCourseRequestController::class,'index'])->name('management.requests');
    Route::get('/management/course-requests/{offeringId}', [AdminCourseRequestController::class,'show'])->name('management.requests.show');
    Route::post('/management/course-requests/{offeringId}/details', [AdminCourseRequestController::class,'updateDetails'])->name('management.requests.details');
    Route::post('/management/course-requests/{offeringId}/decision', [AdminCourseRequestController::class,'decide'])->name('management.requests.decide');
    Route::post('/courses/{offeringId}/save', [CoursePageController::class,'saveCompleted'])->name('courses.save');
    Route::delete('/courses/{offeringId}/save', [CoursePageController::class,'unsaveCompleted'])->name('courses.unsave');

    // ========================================
    // COURSE COVER IMAGES (any signed-in user; approved courses, or the owner / an administrator)
    // ========================================
    Route::get('/course-covers/{offeringId}', [CourseCoverController::class, 'show'])->name('courses.cover');

    // ========================================
    // NOTIFICATIONS (every signed-in user: own inbox only)
    // ========================================
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/alerts/seen', [NotificationController::class, 'alertsSeen'])->name('notifications.alerts-seen');
    Route::get('/notifications/{notificationId}', [NotificationController::class, 'show'])->name('notifications.show');
    Route::post('/notifications/{notificationId}/read', [NotificationController::class, 'read'])->name('notifications.read');

    // ========================================
    // ANNOUNCEMENTS (administrators + instructors; see AnnouncementPolicy)
    // ========================================
    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('/announcements/create', [AnnouncementController::class, 'create'])->name('announcements.create');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::get('/announcements/{announcement}/edit', [AnnouncementController::class, 'edit'])->name('announcements.edit');
    Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::post('/announcements/{announcement}/publish', [AnnouncementController::class, 'publish'])->name('announcements.publish');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');


});
