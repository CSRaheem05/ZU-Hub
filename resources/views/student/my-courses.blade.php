
<!doctype html>
<html lang="en" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>ZU Hub | My Learning</title>

    <link rel="stylesheet"
          href="{{ asset('css/zu-my-courses.css') }}">

    <link rel="stylesheet"
          href="{{ asset('css/zu-student-dashboard.css') }}">
</head>

<body>

<header class="page-header">
    <div class="header-content">

        <a href="{{ route('home') }}" class="brand">
            ⌂ ZU Hub
        </a>

        <div class="header-right">

            <div class="identity">
                <strong>{{ auth()->user()->name }}</strong>
                ·
                <span data-role>
                    {{ ucfirst(auth()->user()->role) }}
                </span>
            </div>

            <button class="language"
                    id="languageButton"
                    type="button">
                العربية
            </button>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button class="logout"
                        type="submit"
                        data-en="Log out"
                        data-ar="تسجيل الخروج">
                    Log out
                </button>
            </form>

        </div>
    </div>
</header>

@php
    $totalCourses = $courses->count();

    $activeCourses = $courses
        ->where('is_active', 1)
        ->count();

    $inactiveCourses = $totalCourses - $activeCourses;
@endphp

<main class="container student-dashboard">

    <!-- =====================================
         WELCOME HERO
         ===================================== -->

    <section class="student-hero">

        <div class="student-hero-content">

            <span class="student-eyebrow"
                  data-en="STUDENT LEARNING SPACE"
                  data-ar="مساحة الطالب التعليمية">
                STUDENT LEARNING SPACE
            </span>

            <h1 data-en="My Learning Journey"
                data-ar="رحلتي التعليمية">
                My Learning Journey
            </h1>

            <p data-en="Keep learning, build your skills, and move closer to your goals."
               data-ar="واصل التعلّم، وطوّر مهاراتك، واقترب من أهدافك.">
                Keep learning, build your skills,
                and move closer to your goals.
            </p>

            <a href="{{ route('courses.index') }}"
               class="student-explore"
               data-en="Explore Courses"
               data-ar="استكشف الدورات">
                Explore Courses
            </a>

        </div>

        <div class="student-hero-art" aria-hidden="true">

            <div class="hero-orbit orbit-one"></div>
            <div class="hero-orbit orbit-two"></div>

            <div class="hero-symbol">
                <svg viewBox="0 0 100 100"
                     fill="none"
                     xmlns="http://www.w3.org/2000/svg">

                    <path d="M10 39L50 19L90 39L50 59L10 39Z"
                          fill="#B9D8FF"/>

                    <path d="M27 50V67C38 77 62 77 73 67V50L50 62L27 50Z"
                          fill="#72B7FF"/>

                    <path d="M90 39V70"
                          stroke="#EAF5FF"
                          stroke-width="4"
                          stroke-linecap="round"/>

                    <circle cx="90" cy="74" r="5"
                            fill="#EAF5FF"/>
                </svg>
            </div>

            <span class="hero-spark spark-one">✦</span>
            <span class="hero-spark spark-two">✧</span>

        </div>

    </section>

    <!-- =====================================
         STATISTICS
         ===================================== -->

    <section class="student-stats">

        <article class="student-stat stat-total">

            <div class="stat-icon">▤</div>

            <div>
                <span data-en="Enrolled Courses"
                      data-ar="الدورات المسجّلة">
                    Enrolled Courses
                </span>

                <strong>{{ $totalCourses }}</strong>
            </div>

        </article>

        <article class="student-stat stat-active">

            <div class="stat-icon">✓</div>

            <div>
                <span data-en="Active Courses"
                      data-ar="الدورات النشطة">
                    Active Courses
                </span>

                <strong>{{ $activeCourses }}</strong>
            </div>

        </article>

        <article class="student-stat stat-inactive">

            <div class="stat-icon">◷</div>

            <div>
                <span data-en="Inactive Courses"
                      data-ar="الدورات الموقوفة">
                    Inactive Courses
                </span>

                <strong>{{ $inactiveCourses }}</strong>
            </div>

        </article>

    </section>

    <!-- =====================================
         SUCCESS AND ERROR MESSAGES
         ===================================== -->

    @if(session('success'))
        <div role="status"
             style="padding:15px; margin:20px 0; background:#e7f7ee; color:#146c43; border-radius:12px;">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div role="alert"
             style="padding:15px; margin:20px 0; background:#fdebec; color:#a52a35; border-radius:12px;">
            {{ session('error') }}
        </div>
    @endif

    <!-- =====================================
         COURSES HEADER
         ===================================== -->

    <section class="student-section-heading">

        <div>

            <span class="student-section-label"
                  data-en="YOUR LEARNING LIBRARY"
                  data-ar="مكتبة دوراتك">
                YOUR LEARNING LIBRARY
            </span>

            <h2 data-en="My Enrolled Courses"
                data-ar="دوراتي المسجّلة">
                My Enrolled Courses
            </h2>

            <p data-en="All your enrolled courses in one organized space."
               data-ar="جميع دوراتك المسجّلة في مكان واحد ومنظّم.">
                All your enrolled courses in one organized space.
            </p>

        </div>

        @if(!$courses->isEmpty())

            <div class="student-filters">

                <input type="search"
                       id="studentCourseSearch"
                       placeholder="Search my courses..."
                       aria-label="Search my courses">

                <select id="studentStatusFilter"
                        aria-label="Filter courses">

                    <option value="all">All Courses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>

                </select>

            </div>

        @endif

    </section>

    <!-- =====================================
         COURSES CONTENT
         ===================================== -->

    @if($courses->isEmpty())

        <section class="student-empty">

            <div class="student-empty-icon"
                 aria-hidden="true">
                ✦
            </div>

            <h2 data-en="Your learning journey starts here"
                data-ar="رحلتك التعليمية تبدأ من هنا">
                Your learning journey starts here
            </h2>

            <p data-en="You haven't enrolled in an ongoing course yet. Your saved completed courses appear below. Explore available courses and choose your next skill."
               data-ar="لم تسجّل في أي دورة بعد. استكشف الدورات المتاحة واختر مهارتك القادمة.">
                You haven't enrolled in an ongoing course yet. Your saved completed courses appear below.
                Explore available courses and choose your next skill.
            </p>

            <a href="{{ route('courses.index') }}"
               class="student-explore"
               data-en="Discover Courses"
               data-ar="اكتشف الدورات">
                Discover Courses
            </a>

        </section>

    @else

        <div class="student-course-grid"
             id="studentCourseGrid">

            @foreach($courses as $course)

                <article class="student-course-card"
                         data-status="{{ $course->is_active ? 'active' : 'inactive' }}"
                         data-search="{{ $course->title }} {{ $course->course_name }} {{ $course->instructor_name }}">

                    <!-- COURSE BANNER -->

                    <div class="student-card-banner">

                        <span class="student-banner-decoration"
                              aria-hidden="true">
                            ◇
                        </span>

                        <span class="student-card-subject">
                            {{ $course->course_name }}
                        </span>

                    </div>

                    <!-- COURSE BODY -->

                    <div class="student-card-body">

                        <div class="student-card-top">

                            <span class="student-card-label"
                                  data-en="ENROLLED COURSE"
                                  data-ar="دورة مسجّلة">
                                ENROLLED COURSE
                            </span>

                            <span class="student-course-status {{ $course->is_active ? 'status-active' : 'status-inactive' }}"
                                  data-en="{{ $course->is_active ? 'Active' : 'Inactive' }}"
                                  data-ar="{{ $course->is_active ? 'نشطة' : 'غير نشطة' }}">
                                {{ $course->is_active ? 'Active' : 'Inactive' }}
                            </span>

                        </div>

                        <h3>{{ $course->title }}</h3>

                        <p class="student-course-description">
                            {{ $course->description ?: 'No description available.' }}
                        </p>

                        <!-- INSTRUCTOR -->

                        <div class="student-instructor">

                            <span class="student-avatar"
                                  aria-hidden="true">
                                {{ mb_strtoupper(mb_substr($course->instructor_name, 0, 1)) }}
                            </span>

                            <div>

                                <small data-en="Your Instructor"
                                       data-ar="مدرّس الدورة">
                                    Your Instructor
                                </small>

                                <strong>
                                    {{ $course->instructor_name }}
                                </strong>

                            </div>

                        </div>

                        <!-- =====================================
                             VIEW COURSE / UNENROLL
                             ===================================== -->

                        <div class="student-course-actions">

                            <a href="{{ route('student.course.show', $course->offering_id) }}"
                               class="student-explore student-course-view"
                               data-en="View Course"
                               data-ar="عرض الدورة">
                                View Course
                            </a>

                            <form method="POST"
                                  action="{{ route('student.course.unenroll', $course->offering_id) }}"
                                  onsubmit="return confirm(document.documentElement.lang === 'ar' ? 'هل أنت متأكد من إلغاء الاشتراك في هذه الدورة؟' : 'Are you sure you want to unenroll from this course?');">

                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                        class="student-course-unenroll"
                                        data-en="Unenroll"
                                        data-ar="إلغاء الاشتراك">
                                    Unenroll
                                </button>

                            </form>

                        </div>

                        <!-- COURSE FOOTER -->

                        <div class="student-card-footer">

                            <span data-en="Learning content"
                                  data-ar="المحتوى التعليمي">
                                Learning content
                            </span>

                            <span class="student-footer-symbol"
                                  aria-hidden="true">
                                ✦
                            </span>

                        </div>

                    </div>

                </article>

            @endforeach

        </div>

        <!-- NO SEARCH RESULTS -->

        <section class="student-empty"
                 id="studentNoResults"
                 hidden>

            <h2>No matching courses</h2>

            <p>
                Try another search term or status.
            </p>

        </section>

    @endif

    <section style="max-width:1100px;margin:35px auto;padding:20px;background:white;border:1px solid #e2e8f0;border-radius:15px">
        <h2>Saved Completed Courses</h2><p>Watch recorded courses anytime, without official enrollment.</p>
        @forelse($saved as $course)
            <div style="padding:16px;margin:12px 0;border:1px solid #e2e8f0;border-radius:12px">
                <strong>{{ $course->title }}</strong> <span>— {{ $course->instructor_name }}</span><br>
                <a href="{{route('student.course.show',$course->offering_id)}}">Watch Course</a>
                <form method="post" style="display:inline;margin-left:16px" action="{{route('courses.unsave',$course->offering_id)}}">@csrf @method('DELETE')<button type="submit">Remove from Saved</button></form>
            </div>
        @empty<p>No saved courses yet.</p>@endforelse
    </section>
</main>

<script src="{{ asset('js/zu-my-courses.js') }}" defer></script>

<script src="{{ asset('js/zu-student-dashboard.js') }}" defer></script>

</body>
</html>
