
<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>{{ $course->title }} | ZU Hub</title>

    <!-- Existing shared styles -->
    <link rel="stylesheet"
          href="{{ asset('css/zu-my-courses.css') }}">

    <!-- Course content styles -->
    <link rel="stylesheet"
          href="{{ asset('css/zu-course-content.css') }}">
</head>

<body>

<main class="learning-page">

    <!-- BACK BUTTON -->
    <a href="{{ route('student.courses') }}"
       class="learning-back">
        ← Back to My Courses
    </a>

    <!-- COURSE INFORMATION -->
    <section class="learning-header">

        <span class="learning-subject">
            {{ $course->course_name }}
        </span>

        <h1>{{ $course->title }}</h1>

        @if($course->description)
            <p class="learning-description">
                {{ $course->description }}
            </p>
        @endif

        <div class="learning-course-info">

            <p>
                <strong>Instructor:</strong>
                {{ $course->instructor_name }}
            </p>

            @if($course->lecture_schedule)
                <p>
                    <strong>Schedule:</strong>
                    {{ $course->lecture_schedule }}
                </p>
            @endif

        </div>

    </section>

    <!-- LESSONS -->
    <section class="learning-lessons">

        <div class="lesson-heading">
            <h2>Course Lessons</h2>

            <p>
                {{ $lessons->count() }}
                {{ $lessons->count() === 1
                    ? 'Lesson'
                    : 'Lessons' }}
            </p>
        </div>

        @if($lessons->isEmpty())

            <!-- EMPTY COURSE STATE -->
            <div class="lesson-empty">

                <div class="waiting-animation"
                     aria-hidden="true">
                    <span>◷</span>
                </div>

                <h2>No Lessons Available Yet</h2>

                <p>
                    Your instructor hasn't added
                    any lessons to this course yet.
                </p>

                <p>
                    Please check back later.
                </p>

            </div>

        @else

            <!-- COURSE LESSONS LIST -->
            <div class="lessons-list">

                @foreach($lessons as $lesson)

                    @php
                        $url = $lesson->lesson_url;
                        $embedUrl = null;
                        $isDirectVideo = false;

                        if (
                            $lesson->lesson_type === 'video' &&
                            $url
                        ) {
                            $parts = parse_url($url);

                            $scheme = strtolower(
                                $parts['scheme'] ?? ''
                            );

                            $host = strtolower(
                                $parts['host'] ?? ''
                            );

                            $path = $parts['path'] ?? '';

                            if (
                                in_array(
                                    $scheme,
                                    ['http', 'https'],
                                    true
                                )
                            ) {
                                $youtubeHosts = [
                                    'youtube.com',
                                    'www.youtube.com',
                                    'm.youtube.com',
                                    'youtu.be',
                                    'www.youtu.be'
                                ];

                                $videoId = null;

                                if (
                                    in_array(
                                        $host,
                                        $youtubeHosts,
                                        true
                                    )
                                ) {
                                    if (
                                        $host === 'youtu.be' ||
                                        $host === 'www.youtu.be'
                                    ) {
                                        $videoId = trim(
                                            $path,
                                            '/'
                                        );
                                    } elseif ($path === '/watch') {
                                        parse_str(
                                            $parts['query'] ?? '',
                                            $query
                                        );

                                        $videoId =
                                            $query['v'] ?? null;
                                    } elseif (
                                        preg_match(
                                            '~^/(embed|shorts|live)/([A-Za-z0-9_-]{11})~',
                                            $path,
                                            $matches
                                        )
                                    ) {
                                        $videoId = $matches[2];
                                    }

                                    if (
                                        is_string($videoId) &&
                                        preg_match(
                                            '/^[A-Za-z0-9_-]{11}$/',
                                            $videoId
                                        )
                                    ) {
                                        $embedUrl =
                                            'https://www.youtube-nocookie.com/embed/'
                                            . $videoId;
                                    }
                                }

                                if (
                                    !$embedUrl &&
                                    preg_match(
                                        '/\.mp4$/i',
                                        $path
                                    )
                                ) {
                                    $isDirectVideo = true;
                                }
                            }
                        }
                    @endphp

                    <article class="lesson-card">

                        <!-- LESSON INFORMATION -->
                        <div class="lesson-type-label">
                            Lesson {{ $lesson->lesson_order }}
                            ·
                            {{ ucfirst($lesson->lesson_type) }}
                        </div>

                        <h3>{{ $lesson->lesson_title }}</h3>

                        <!-- LIVE SESSION -->
                        @if($lesson->lesson_type === 'live')

                            @if($lesson->scheduled_at)
                                <p class="lesson-date">
                                    Scheduled:
                                    {{ $lesson->scheduled_at }}
                                </p>
                            @endif

                            @if($lesson->live_url)

                                <a class="lesson-link"
                                   href="{{ $lesson->live_url }}"
                                   target="_blank"
                                   rel="noopener noreferrer">
                                    Join Live Session
                                </a>

                            @else

                                <p>
                                    Live session link
                                    is not available yet.
                                </p>

                            @endif

                        <!-- VIDEO LESSON -->
                        @elseif($lesson->lesson_type === 'video')

                            @if($embedUrl)

                                <iframe
                                    class="lesson-frame"
                                    src="{{ $embedUrl }}"
                                    title="{{ $lesson->lesson_title }}"
                                    loading="lazy"
                                    referrerpolicy="strict-origin-when-cross-origin"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    allowfullscreen>
                                </iframe>

                            @elseif($isDirectVideo)

                                <video class="lesson-video"
                                       controls
                                       preload="metadata">

                                    <source
                                        src="{{ $url }}"
                                        type="video/mp4">

                                    Your browser does not
                                    support video playback.
                                </video>

                            @elseif($url)

                                <p>
                                    This video cannot be
                                    embedded directly.
                                </p>

                                <a class="lesson-link"
                                   href="{{ $url }}"
                                   target="_blank"
                                   rel="noopener noreferrer">
                                    Open Video
                                </a>

                            @else

                                <p>
                                    Video not available yet.
                                </p>

                            @endif

                        <!-- EXTERNAL RESOURCE -->
                        @elseif($lesson->lesson_type === 'link')

                            @if($lesson->lesson_url)

                                <a class="lesson-link"
                                   href="{{ $lesson->lesson_url }}"
                                   target="_blank"
                                   rel="noopener noreferrer">
                                    Open Learning Resource
                                </a>

                            @else

                                <p>
                                    Resource not available yet.
                                </p>

                            @endif

                        @endif

                    </article>

                @endforeach

            </div>

        @endif

    </section>

</main>

</body>
</html>
