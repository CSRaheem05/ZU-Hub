<!doctype html>
<html lang="en" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        {{ $course ? 'Edit Course' : 'Create Course' }} | ZU Hub
    </title>

    <link rel="stylesheet"
          href="{{ asset('css/zu-my-courses.css') }}">

    <link rel="stylesheet"
          href="{{ asset('css/zu-course-form.css') }}">
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
                · Instructor
            </div>

            <a href="{{ route('instructor.courses') }}"
               class="back">
                My Courses
            </a>
        </div>

    </div>
</header>

<main class="zc-container">

    <div class="zc-heading">
        <p class="zc-eyebrow">INSTRUCTOR WORKSPACE</p>

        <h1>
            {{ $course ? 'Edit Course' : 'Create Course' }}
        </h1>

        <p>
            {{ $course
                ? 'Update the requested details and resubmit for admin approval.'
                : 'Submit your course details for admin approval.' }}
        </p>
    </div>

    @if($errors->any())
        <div class="zc-errors" role="alert">
            <strong>Please correct these errors:</strong>

            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($course && $course->review_note)
        <div style="background:#fff7e7;border-left:4px solid #e0a239;padding:15px;margin:18px 0;border-radius:8px"><strong>Admin Feedback</strong><p>{{ $course->review_note }}</p></div>
    @endif
    <form id="courseForm"
          method="POST"
          action="{{ $course
              ? route('instructor.courses.update', $course->offering_id)
              : route('instructor.courses.store') }}">

        @csrf

        @if($course)
            @method('PUT')
        @endif

        <!-- 1. Academic Information -->

        <section class="zc-card">

            <h2>01. Academic Information</h2>
            <p>Select the college, department and subject.</p>

            <div class="zc-field">
                <label for="collegeToggle">College *</label>

                <div class="zc-dropdown" id="collegeDropdown">
                    <button type="button"
                            id="collegeToggle"
                            class="zc-dropdown-toggle"
                            aria-expanded="false"
                            aria-controls="collegePanel">
                        <span id="collegeLabel">Select College</span>
                        <span aria-hidden="true">▾</span>
                    </button>

                    <div class="zc-dropdown-panel"
                         id="collegePanel" hidden>
                        <input type="search"
                               id="collegeSearch"
                               aria-label="Search colleges"
                               placeholder="Search colleges..."
                               autocomplete="off">

                        <div class="zc-options"
                             id="collegeOptions"></div>
                    </div>
                </div>

                <input type="hidden" id="college_id">
            </div>

            <div class="zc-field">
                <label for="departmentToggle">Department *</label>

                <div class="zc-dropdown" id="departmentDropdown">
                    <button type="button"
                            id="departmentToggle"
                            class="zc-dropdown-toggle"
                            aria-expanded="false"
                            aria-controls="departmentPanel"
                            disabled>
                        <span id="departmentLabel">
                            Select College First
                        </span>
                        <span aria-hidden="true">▾</span>
                    </button>

                    <div class="zc-dropdown-panel"
                         id="departmentPanel" hidden>
                        <input type="search"
                               id="departmentSearch"
                               aria-label="Search departments"
                               placeholder="Search departments..."
                               autocomplete="off">

                        <div class="zc-options"
                             id="departmentOptions"></div>
                    </div>
                </div>

                <input type="hidden" id="department_id">
            </div>

            <div class="zc-field">
                <label for="subjectToggle">Subject *</label>

                <div class="zc-dropdown" id="subjectDropdown">
                    <button type="button"
                            id="subjectToggle"
                            class="zc-dropdown-toggle"
                            aria-expanded="false"
                            aria-controls="subjectPanel"
                            disabled>
                        <span id="subjectLabel">
                            Select Department First
                        </span>
                        <span aria-hidden="true">▾</span>
                    </button>

                    <div class="zc-dropdown-panel"
                         id="subjectPanel" hidden>
                        <input type="search"
                               id="subjectSearch"
                               aria-label="Search subjects"
                               placeholder="Search by name or code..."
                               autocomplete="off">

                        <div class="zc-options"
                             id="subjectOptions"></div>
                    </div>
                </div>

                <input type="hidden"
                       name="course_id"
                       id="course_id"
                       value="{{ old('course_id', $course->course_id ?? '') }}">
            </div>

        </section>

        <!-- 2. Course Details -->

        <section class="zc-card">

            <h2>02. Course Details</h2>
            <p>Describe your training course.</p>

            <div class="zc-field">
                <label for="title">Course Title *</label>

                <input type="text"
                       name="title"
                       id="title"
                       maxlength="255"
                       required
                       placeholder="Example: Java Programming Basics"
                       value="{{ old('title', $course->title ?? '') }}">
            </div>

            <div class="zc-field">
                <label for="description">Description</label>

                <textarea name="description"
                          id="description"
                          rows="5"
                          placeholder="What will students learn?">{{ old('description', $course->description ?? '') }}</textarea>
            </div>

        </section>

        <!-- 3. Course Duration -->

        <section class="zc-card">

            <h2>03. Course Duration</h2>
            <p>Enter the estimated duration.</p>

            <div class="zc-grid">

                <div class="zc-field">
                    <label for="durationHours">Hours</label>

                    <input type="number"
                           id="durationHours"
                           min="0"
                           max="9999"
                           step="1"
                           placeholder="0">
                </div>

                <div class="zc-field">
                    <label for="durationMinutes">Minutes</label>

                    <input type="number"
                           id="durationMinutes"
                           min="0"
                           max="59"
                           step="1"
                           value="0">
                </div>

            </div>

            <input type="hidden"
                   name="duration_hours"
                   id="duration_hours"
                   value="{{ old('duration_hours', $course->duration_hours ?? '') }}">

            <div class="zc-preview" id="durationPreview">
                Duration not specified.
            </div>

        </section>

        <!-- 4. Lecture Schedule -->

        <section class="zc-card">

            <h2>04. Lecture Schedule</h2>
            <p>Select lecture days and starting time.</p>

            <div class="zc-field">
                <label>Lecture Days</label>

                <div class="zc-days">

                    @foreach([
                        'Sunday', 'Monday', 'Tuesday',
                        'Wednesday', 'Thursday',
                        'Friday', 'Saturday'
                    ] as $day)

                        <label class="zc-day">
                            <input type="checkbox"
                                   class="lectureDay"
                                   value="{{ $day }}">

                            {{ $day }}
                        </label>

                    @endforeach

                </div>
            </div>

            <div class="zc-field">
                <label for="lectureTime">Start Time</label>

                <input type="time" id="lectureTime">
            </div>

            <input type="hidden"
                   name="lecture_schedule"
                   id="lecture_schedule"
                   value="{{ old('lecture_schedule', $course->lecture_schedule ?? '') }}">

            <div class="zc-preview" id="schedulePreview">
                No lecture schedule selected.
            </div>

        </section>

        <section style="padding:15px;background:#f2f6fc;border-radius:10px;margin:15px 0">
            <label for="topic_id"><strong>Course Topic</strong></label>
            <select name="topic_id" id="topic_id" style="display:block;width:100%;padding:10px;margin-top:8px">
                <option value="">General / Not specified</option>
                @foreach($topics as $topic)
                    <option value="{{ $topic->topic_id }}" data-college="{{ $topic->college_code }}" @selected(old('topic_id',$course->topic_id ?? '')===$topic->topic_id)>{{ $topic->topic_name }}</option>
                @endforeach
            </select><small>Choose a topic belonging to the same college as the related university course.</small>
        </section>
        <!-- Actions -->

        <div class="zc-actions">

            <a href="{{ route('instructor.courses') }}"
               class="zc-btn zc-secondary">
                Cancel
            </a>

            <button type="submit"
                    class="zc-btn zc-primary">

                {{ $course ? 'Resubmit for Approval' : 'Submit for Approval' }}

            </button>

        </div>

    </form>

</main>

<!-- Subject data from Laravel -->
<script id="subjectsData" type="application/json">
{!! json_encode($subjects, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>

<script src="{{ asset('js/zu-course-form.js') }}" defer></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
 const college = document.getElementById('college_id');
 const topic = document.getElementById('topic_id');
 if (!college || !topic) return;
 function refreshTopics() {
   const code = college.value;
   Array.from(topic.options).forEach(option => {
     if (!option.value) return;
     const allowed = option.dataset.college === code;
     option.hidden = !allowed;
     option.disabled = !allowed;
   });
   if (topic.selectedOptions[0]?.disabled) topic.value = '';
 }
 document.addEventListener('click', () => setTimeout(refreshTopics, 0));
 college.addEventListener('change', refreshTopics);
 refreshTopics();
});
</script>
</body>
</html>
