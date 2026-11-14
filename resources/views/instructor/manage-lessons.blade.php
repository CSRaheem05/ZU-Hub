
<!doctype html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Lessons | ZU Hub</title>

    <link rel="stylesheet" href="{{ asset('css/zu-my-courses.css') }}">
    <link rel="stylesheet" href="{{ asset('css/zu-instructor-dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/zu-manage-lessons.css') }}">
</head>

<body>

<header class="page-header">
    <div class="header-content">
        <a href="{{ route('home') }}" class="brand">⌂ ZU Hub</a>

        <div class="header-right">
            <div class="identity">
                <strong>{{ auth()->user()->name }}</strong>
                · Instructor
            </div>

            <a href="{{ route('instructor.courses') }}"
               class="dash-btn dash-btn-light">
                My Courses
            </a>
        </div>
    </div>
</header>

<main class="container dash-page">

    <section class="dash-hero">
        <div>
            <div class="dash-eyebrow">INSTRUCTOR WORKSPACE</div>
            <h1>Manage Lessons</h1>
            <p>{{ $course->title }}</p>
        </div>

        <a href="{{ route('instructor.courses') }}"
           class="dash-create">
            Back to Courses
        </a>
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

    @if($errors->any())
        <div class="dash-message dash-error" role="alert">
            <strong>Please correct these errors:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="lesson-card">
        <div class="lesson-heading">
            <div>
                <h2>Add New Lesson</h2>
                <p>Add a video link, an external resource or a live session.</p>
            </div>
        </div>

        <form method="POST"
              action="{{ route('instructor.lessons.store', $course->offering_id) }}"
              class="lesson-form">

            @csrf

            <div class="lesson-field">
                <label for="newLessonTitle">Lesson Title *</label>
                <input type="text"
                       id="newLessonTitle"
                       name="lesson_title"
                       maxlength="255"
                       required
                       value="{{ old('lesson_title') }}"
                       placeholder="Example: Introduction to Java">
            </div>

            <div class="lesson-grid">
                <div class="lesson-field">
                    <label for="newLessonType">Lesson Type *</label>
                    <select id="newLessonType"
                            name="lesson_type"
                            class="lesson-type"
                            required>
                        <option value="video" @selected(old('lesson_type') === 'video')>Video</option>
                        <option value="link" @selected(old('lesson_type') === 'link')>External Link</option>
                        <option value="live" @selected(old('lesson_type') === 'live')>Live Session</option>
                    </select>
                </div>

                <div class="lesson-field">
                    <label for="newLessonOrder">Lesson Order *</label>
                    <input type="number"
                           id="newLessonOrder"
                           name="lesson_order"
                           min="0"
                           step="1"
                           required
                           value="{{ old('lesson_order', $lessons->count() + 1) }}">
                </div>
            </div>

            <div class="lesson-field lesson-video-fields">
                <label>Video / Resource URL *</label>
                <input type="url"
                       name="lesson_url"
                       placeholder="https://..."
                       value="{{ old('lesson_url') }}">
            </div>

            <div class="lesson-live-fields" hidden>
                <div class="lesson-field">
                    <label>Live Session URL *</label>
                    <input type="url"
                           name="live_url"
                           placeholder="https://..."
                           value="{{ old('live_url') }}">
                </div>

                <div class="lesson-field">
                    <label>Scheduled Date & Time *</label>
                    <input type="datetime-local"
                           name="scheduled_at"
                           value="{{ old('scheduled_at') }}">
                </div>
            </div>

            <button type="submit" class="dash-btn dash-btn-primary">
                + Add Lesson
            </button>
        </form>
    </section>

    <section class="lesson-card">
        <div class="lesson-heading">
            <div>
                <h2>Course Lessons</h2>
                <p>{{ $lessons->count() }} lesson(s)</p>
            </div>
        </div>

        @forelse($lessons as $lesson)
            <article class="lesson-item">

                <div class="lesson-item-top">
                    <div>
                        <span class="lesson-tag">
                            {{ ucfirst($lesson->lesson_type) }}
                        </span>

                        <h3>
                            {{ $lesson->lesson_order }}.
                            {{ $lesson->lesson_title }}
                        </h3>
                    </div>

                    <button type="button"
                            class="dash-btn dash-btn-light lesson-edit-toggle"
                            aria-expanded="false"
                            aria-controls="lessonEdit{{ $loop->index }}">
                        Edit Lesson
                    </button>
                </div>

                @if($lesson->lesson_type === 'live')
                    <p class="lesson-meta">
                        Scheduled:
                        {{ $lesson->scheduled_at ?: 'Not scheduled' }}
                    </p>

                    @if($lesson->live_url)
                        <a href="{{ $lesson->live_url }}"
                           target="_blank"
                           rel="noopener noreferrer">
                            Open Live Link
                        </a>
                    @endif
                @else
                    @if($lesson->lesson_url)
                        <a href="{{ $lesson->lesson_url }}"
                           target="_blank"
                           rel="noopener noreferrer">
                            Open Lesson Link
                        </a>
                    @endif
                @endif

                <div class="lesson-edit-panel"
                     id="lessonEdit{{ $loop->index }}"
                     hidden>

                    <form method="POST"
                          class="lesson-form"
                          action="{{ route('instructor.lessons.update', [
                              $course->offering_id,
                              $lesson->lesson_id
                          ]) }}">

                        @csrf
                        @method('PUT')

                        <div class="lesson-field">
                            <label>Lesson Title *</label>
                            <input type="text"
                                   name="lesson_title"
                                   maxlength="255"
                                   required
                                   value="{{ $lesson->lesson_title }}">
                        </div>

                        <div class="lesson-grid">
                            <div class="lesson-field">
                                <label>Lesson Type *</label>
                                <select name="lesson_type"
                                        class="lesson-type"
                                        required>
                                    <option value="video" @selected($lesson->lesson_type === 'video')>Video</option>
                                    <option value="link" @selected($lesson->lesson_type === 'link')>External Link</option>
                                    <option value="live" @selected($lesson->lesson_type === 'live')>Live Session</option>
                                </select>
                            </div>

                            <div class="lesson-field">
                                <label>Lesson Order *</label>
                                <input type="number"
                                       name="lesson_order"
                                       min="0"
                                       step="1"
                                       required
                                       value="{{ $lesson->lesson_order }}">
                            </div>
                        </div>

                        <div class="lesson-field lesson-video-fields">
                            <label>Video / Resource URL *</label>
                            <input type="url"
                                   name="lesson_url"
                                   value="{{ $lesson->lesson_url }}">
                        </div>

                        <div class="lesson-live-fields" hidden>
                            <div class="lesson-field">
                                <label>Live Session URL *</label>
                                <input type="url"
                                       name="live_url"
                                       value="{{ $lesson->live_url }}">
                            </div>

                            <div class="lesson-field">
                                <label>Scheduled Date & Time *</label>
                                <input type="datetime-local"
                                       name="scheduled_at"
                                       value="{{ $lesson->scheduled_at
                                           ? \Carbon\Carbon::parse($lesson->scheduled_at)->format('Y-m-d\TH:i')
                                           : '' }}">
                            </div>
                        </div>

                        <button type="submit"
                                class="dash-btn dash-btn-primary">
                            Save Lesson Changes
                        </button>
                    </form>

                    <form method="POST"
                          class="lesson-delete-form"
                          action="{{ route('instructor.lessons.destroy', [
                              $course->offering_id,
                              $lesson->lesson_id
                          ]) }}">
                        @csrf
                        @method('DELETE')

                        <button type="submit"
                                class="dash-btn dash-btn-danger">
                            Delete Lesson
                        </button>
                    </form>
                </div>

            </article>

        @empty
            <div class="dash-empty">
                <h3>No lessons yet</h3>
                <p>Add your first lesson using the form above.</p>
            </div>
        @endforelse

    </section>

</main>

<script src="{{ asset('js/zu-manage-lessons.js') }}" defer></script>

</body>
</html>
