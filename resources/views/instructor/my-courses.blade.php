
<!doctype html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ZU Hub | My Courses</title>

    <link rel="stylesheet" href="{{ asset('css/zu-my-courses.css') }}">
    <link rel="stylesheet" href="{{ asset('css/zu-instructor-dashboard.css') }}">
</head>

<body>

<header class="page-header">
    <div class="header-content">
        <a href="{{ route('home') }}" class="brand">⌂ ZU Hub</a>

        <div class="header-right">
            <div class="identity">
                <strong>{{ auth()->user()->name }}</strong>
                · <span data-role>{{ ucfirst(auth()->user()->role) }}</span>
            </div>

            <button class="language" id="languageButton" type="button">
                العربية
            </button>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="logout" type="submit"
                        data-en="Log out" data-ar="تسجيل الخروج">
                    Log out
                </button>
            </form>
        </div>
    </div>
</header>

@php
    $totalCourses = $courses->count();
    $activeCourses = $courses->where('is_active', 1)->count();
    $inactiveCourses = $totalCourses - $activeCourses;
    $totalStudents = $courses->sum('students_count');
@endphp

<main class="container dash-page">

    <section class="dash-hero">
        <div>
            <div class="dash-eyebrow"
                 data-en="INSTRUCTOR WORKSPACE"
                 data-ar="مساحة المدرس">
                INSTRUCTOR WORKSPACE
            </div>

            <h1 data-en="My Courses" data-ar="دوراتي">
                My Courses
            </h1>

            <p data-en="Create, organize and manage your training courses."
               data-ar="أنشئ دوراتك التدريبية ونظمها وقم بإدارتها.">
                Create, organize and manage your training courses.
            </p>
        </div>

        <a href="{{ route('instructor.courses.create') }}"
           class="dash-create"
           data-en="+ Create Course"
           data-ar="+ إنشاء دورة">
            + Create Course
        </a>
    </section>

    <section class="dash-stats">

        <article class="dash-stat dash-stat-total">
            <span data-en="Total Courses" data-ar="إجمالي الدورات">
                Total Courses
            </span>
            <strong>{{ $totalCourses }}</strong>
        </article>

        <article class="dash-stat dash-stat-active">
            <span data-en="Active Courses" data-ar="الدورات النشطة">
                Active Courses
            </span>
            <strong>{{ $activeCourses }}</strong>
        </article>

        <article class="dash-stat dash-stat-inactive">
            <span data-en="Inactive Courses" data-ar="الدورات الموقوفة">
                Inactive Courses
            </span>
            <strong>{{ $inactiveCourses }}</strong>
        </article>

        <article class="dash-stat dash-stat-students">
            <span data-en="Total Enrollments" data-ar="إجمالي التسجيلات">
                Total Enrollments
            </span>
            <strong>{{ $totalStudents }}</strong>
        </article>

    </section>

    @if(session('success'))
        <div class="dash-message dash-success" role="status">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="dash-message dash-error" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <section class="dash-toolbar">
        <div class="dash-toolbar-heading">
            <h2 data-en="Your Courses" data-ar="دوراتك">
                Your Courses
            </h2>
            <span id="courseResultCount"></span>
        </div>

        <div class="dash-filters">
            <input type="search"
                   id="courseSearch"
                   placeholder="Search courses..."
                   aria-label="Search courses"
                   autocomplete="off">

            <select id="courseStatus" aria-label="Filter courses">
                <option value="all">All Courses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
    </section>

    @if($courses->isEmpty())

        <section class="dash-empty">
            <h2>No courses yet</h2>
            <p>Create your first training course to get started.</p>
            <a href="{{ route('instructor.courses.create') }}"
               class="dash-btn dash-btn-primary">
                Create Your First Course
            </a>
        </section>

    @else

        <div class="dash-cards" id="courseCards">

            @foreach($courses as $course)

                @php
                    $duration = $course->duration_hours;

                    $totalMinutes = $duration === null
                        ? null
                        : round((float) $duration * 60);

                    $hours = $totalMinutes === null
                        ? 0
                        : intdiv((int) $totalMinutes, 60);

                    $minutes = $totalMinutes === null
                        ? 0
                        : (int) $totalMinutes % 60;
                @endphp

                <article class="dash-course"
                         data-status="{{ $course->is_active ? 'active' : 'inactive' }}"
                         data-search="{{ $course->title }} {{ $course->course_name }} {{ $course->description }}">

                    <div class="dash-course-head">
                        <span class="dash-subject">
                            {{ $course->course_name }}
                        </span>

                        <span class="dash-status {{ $course->is_active ? 'is-active' : 'is-inactive' }}">
                            {{ $course->is_active ? '● Active' : '● Inactive' }}
                        </span>
                    </div>

                    <h2>{{ $course->title }}</h2>

                    <p class="dash-description">
                        {{ $course->description ?: 'No description provided.' }}
                    </p>

                    <div class="dash-details">
                        <div>
                            <span>Enrolled Students</span>
                            <strong>{{ $course->students_count }}</strong>
                        </div>

                        <div>
                            <span>Duration</span>
                            <strong>
                                @if($totalMinutes === null)
                                    Not specified
                                @else
                                    {{ $hours }}h {{ $minutes }}m
                                @endif
                            </strong>
                        </div>
                    </div>

                    <div class="dash-schedule">
                        <strong>Lecture Schedule</strong>
                        <p>
                            {{ $course->lecture_schedule ?: 'Not scheduled yet.' }}
                        </p>
                    </div>

                    <div class="dash-actions">

                        <a class="dash-btn dash-btn-primary"
                           href="{{ route('instructor.lessons.index', $course->offering_id) }}">
                            Manage Lessons
                        </a>

                        <a class="dash-btn dash-btn-light"
                           href="{{ route('instructor.students', $course->offering_id) }}">
                            View Students
                        </a>

                        <a class="dash-btn dash-btn-light"
                           href="{{ route('instructor.courses.edit', $course->offering_id) }}">
                            Edit
                        </a>

                        <form method="POST"
                              action="{{ route('instructor.courses.status', $course->offering_id) }}">
                            @csrf
                            @method('PATCH')

                            <button type="submit" class="dash-btn dash-btn-outline">
                                {{ $course->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>

                        <form method="POST"
                              action="{{ route('instructor.courses.destroy', $course->offering_id) }}"
                              class="dash-delete-form"
                              data-course-title="{{ $course->title }}">
                            @csrf
                            @method('DELETE')

                            <button type="submit" class="dash-btn dash-btn-danger">
                                Delete
                            </button>
                        </form>

                    </div>

                </article>

            @endforeach

        </div>

        <section class="dash-empty" id="noSearchResults" hidden>
            <h2>No matching courses</h2>
            <p>Try another search or status filter.</p>
        </section>

    @endif

</main>

<script src="{{ asset('js/zu-my-courses.js') }}" defer></script>
<script src="{{ asset('js/zu-instructor-dashboard.js') }}" defer></script>

</body>
</html>
