
<!doctype html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Available Courses | ZU Hub</title>

    <link rel="stylesheet" href="{{ asset('css/zu-pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/zu-available-courses.css') }}">
</head>

<body>

<header>
    <a href="{{ route('home') }}" class="home" aria-label="Home">
        ⌂ ZU Hub
    </a>

    <div>
        <span>
            {{ auth()->user()->name }}
            ·
            {{ ucfirst(auth()->user()->role) }}
        </span>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Log out / خروج</button>
        </form>
    </div>
</header>

<main class="available-page">

    <section class="available-hero">
        <div class="available-hero-content">

            <span class="available-eyebrow"
                  data-en="DISCOVER YOUR NEXT SKILL"
                  data-ar="اكتشف مهارتك القادمة">
                DISCOVER YOUR NEXT SKILL
            </span>

            <h1 data-en="Explore Courses"
                data-ar="استكشف الدورات">
                Explore Courses
            </h1>

            <p data-en="Discover training courses taught by university instructors and start learning something new."
               data-ar="اكتشف الدورات التدريبية التي يقدمها مدرسو الجامعة وابدأ بتعلم مهارة جديدة.">
                Discover training courses taught by university instructors
                and start learning something new.
            </p>

        </div>

        <div class="available-hero-art" aria-hidden="true">
            <span>✦</span>
            <span>✧</span>
            <div class="available-hero-book">▤</div>
        </div>
    </section>

    @if(session('success'))
        <div class="available-message" role="status">
            {{ session('success') }}
        </div>
    @endif

    <section class="available-toolbar">
        <div>
            <span class="available-section-label"
                  data-en="COURSE CATALOG"
                  data-ar="دليل الدورات">
                COURSE CATALOG
            </span>

            <h2 data-en="Available Training Courses"
                data-ar="الدورات التدريبية المتاحة">
                Available Training Courses
            </h2>

            <p>
                <span id="availableCount">{{ $courses->count() }}</span>
                <span data-en="courses available"
                      data-ar="دورة متاحة">
                    courses available
                </span>
            </p>
        </div>

        <input type="search"
               id="availableSearch"
               placeholder="Search by course or instructor..."
               aria-label="Search courses">
    </section>

    @if($courses->isEmpty())

        <section class="available-empty">
            <div class="available-empty-icon">✦</div>

            <h2 data-en="No courses available yet"
                data-ar="لا توجد دورات متاحة حاليًا">
                No courses available yet
            </h2>

            <p data-en="New courses will appear here when instructors publish them."
               data-ar="ستظهر الدورات الجديدة هنا عندما يقوم المدرسون بنشرها.">
                New courses will appear here when instructors publish them.
            </p>
        </section>

    @else

        <div class="available-grid" id="availableGrid">

            @foreach($courses as $course)

                @php
                    $isStudent = auth()->user()->role === 'student';

                    $isEnrolled = $isStudent &&
                        $enrolledOfferingIds->contains($course->offering_id);

                    $totalMinutes = $course->duration_hours === null
                        ? null
                        : (int) round((float) $course->duration_hours * 60);
                @endphp

                <article class="available-card"
                         data-search="{{ $course->title }} {{ $course->course_name }} {{ $course->instructor_name }}">

                    <div class="available-card-banner">
                        <div class="available-banner-symbol"
                             aria-hidden="true">
                            ✧
                        </div>

                        <span class="available-subject">
                            {{ $course->course_name }}
                        </span>
                    </div>

                    <div class="available-card-content">

                        <div class="available-card-top">
                            <span class="available-label"
                                  data-en="TRAINING COURSE"
                                  data-ar="دورة تدريبية">
                                TRAINING COURSE
                            </span>

                            <span class="available-active"
                                  data-en="● Active"
                                  data-ar="● نشطة">
                                ● Active
                            </span>
                        </div>

                        <h3>{{ $course->title }}</h3>

                        <p class="available-description">
                            {{ $course->description ?: 'No description provided.' }}
                        </p>

                        <div class="available-instructor">
                            <div class="available-avatar" aria-hidden="true">
                                {{ mb_strtoupper(mb_substr($course->instructor_name, 0, 1)) }}
                            </div>

                            <div>
                                <small data-en="Course Instructor"
                                       data-ar="مدرس الدورة">
                                    Course Instructor
                                </small>

                                <strong>{{ $course->instructor_name }}</strong>
                            </div>
                        </div>

                        <div class="available-meta">
                            <span>
                                ◷
                                @if($totalMinutes === null)
                                    Duration not specified
                                @else
                                    {{ intdiv($totalMinutes, 60) }}h
                                    {{ $totalMinutes % 60 }}m
                                @endif
                            </span>
                        </div>

                        <div class="available-card-actions">

                            @if($isStudent)

                                @if($isEnrolled)

                                    <span class="available-enrolled"
                                          data-en="✓ Already Enrolled"
                                          data-ar="✓ مسجّل بالفعل">
                                        ✓ Already Enrolled
                                    </span>

                                    <a href="{{ route('student.courses') }}"
                                       class="available-btn available-secondary"
                                       data-en="My Courses"
                                       data-ar="دوراتي">
                                        My Courses
                                    </a>

                                @else

                                    <form method="POST"
                                          action="{{ route('courses.enroll', $course->offering_id) }}">
                                        @csrf

                                        <button type="submit"
                                                class="available-btn available-primary"
                                                data-en="Enroll Now"
                                                data-ar="سجّل الآن">
                                            Enroll Now
                                        </button>
                                    </form>

                                @endif

                            @else

                                <span class="available-role-note"
                                      data-en="Enrollment is available to students."
                                      data-ar="التسجيل متاح للطلاب فقط.">
                                    Enrollment is available to students.
                                </span>

                            @endif

                        </div>

                    </div>

                </article>

            @endforeach

        </div>

        <section class="available-empty"
                 id="availableNoResults"
                 hidden>
            <h2 data-en="No matching courses"
                data-ar="لا توجد دورات مطابقة">
                No matching courses
            </h2>

            <p data-en="Try searching for another course or instructor."
               data-ar="جرّب البحث عن دورة أو مدرس آخر.">
                Try searching for another course or instructor.
            </p>
        </section>

    @endif

</main>

<script src="{{ asset('js/zu-available-courses.js') }}" defer></script>

</body>
</html>
