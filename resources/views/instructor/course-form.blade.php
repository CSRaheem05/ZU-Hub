@extends('layouts.glass')

@php
    // create   : a brand-new request, sent for approval
    // resubmit : the administration asked for changes; saving sends it back for approval
    // edit     : an approved course; only details that do not change what was approved
    $mode = ! $course ? 'create' : ($course->approval_status === 'approved' ? 'edit' : 'resubmit');
    $locked = $mode === 'edit';

    $subject = $course ? $subjects->firstWhere('course_id', $course->course_id) : null;
    $topicName = $course && $course->topic_id ? optional($topics->firstWhere('topic_id', $course->topic_id))->topic_name : null;

    $heading = ['create' => 'Create Course', 'resubmit' => 'Update Request', 'edit' => 'Edit Course'][$mode];
    $submitLabel = ['create' => 'Submit for approval', 'resubmit' => 'Resubmit for approval', 'edit' => 'Save changes'][$mode];
    $leaveUrl = $mode === 'edit' ? route('instructor.courses') : route('instructor.requests');

    $coverUrl = ($course && $course->cover_image) ? route('courses.cover', $course->offering_id).'?v='.substr(md5($course->cover_image), 0, 10) : null;
    $previewCourse = $course ?: (object) ['offering_id' => 'new-course-preview', 'title' => 'Your course', 'cover_image' => null];
    $previewDesc = trim((string) old('description', $course->description ?? ''));
@endphp

@section('title', $heading)
@section('footer_right', 'Zarqa University · Instructor workspace')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/zu-instructor.css') }}?v={{ @filemtime(public_path('css/zu-instructor.css')) }}">
<link rel="stylesheet" href="{{ asset('css/zu-instructor-form.css') }}?v={{ @filemtime(public_path('css/zu-instructor-form.css')) }}">
@endpush

@section('content')

{{-- ================================================================ heading --}}
<header class="iw-pagehead reveal" style="--i:0">
    <h1>
        <span class="iw-pagehead__icon" aria-hidden="true">
            @if($mode === 'create')<svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>@else<svg viewBox="0 0 24 24"><path d="M4 20h4L19 9a2.800 2.800 0 0 0-4-4L4 16z"/><path d="m13.500 6.500 4 4"/></svg>@endif
        </span>
        {{ $heading }}
    </h1>
    @if($mode !== 'create')
        <a href="{{ $leaveUrl }}" class="iw-pagehead__back">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
            {{ $mode === 'edit' ? 'Back to My Courses' : 'Back to requests' }}
        </a>
    @endif
</header>

@if($mode === 'resubmit' && $course->review_note)
    <div class="iw-feedback iw-feedback--banner reveal" style="--i:1; --rq:#dc9300">
        <span class="iw-feedback__label"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.600 7.100L4 20l1-4.800A8 8 0 1 1 21 12z"/></svg>Administration feedback</span>
        <p>{{ $course->review_note }}</p>
    </div>
@endif

<form id="courseForm" class="iw-form" method="POST" enctype="multipart/form-data" novalidate data-submit-once data-mode="{{ $mode }}"
      action="{{ $course ? route('instructor.courses.update', $course->offering_id) : route('instructor.courses.store') }}">
    @csrf
    @if($course) @method('PUT') @endif

    {{-- ================================================================ the sheet: four connected steps --}}
    <div class="iw-sheet reveal" style="--i:1">

        {{-- ============================================================ 1 · cover --}}
        <section class="iw-section">
            <span class="iw-section__no">1</span>
            <div class="iw-section__body">
                <h2>Cover image <span class="iw-opt">optional</span></h2>

                <div class="iw-drop" id="coverDrop" data-existing="{{ $coverUrl }}" data-max-kb="{{ \App\Services\CourseCoverService::MAX_KILOBYTES }}"
                     data-min-w="{{ \App\Services\CourseCoverService::MIN_WIDTH }}" data-min-h="{{ \App\Services\CourseCoverService::MIN_HEIGHT }}" data-max-side="{{ \App\Services\CourseCoverService::MAX_SIDE }}">
                    <input type="file" name="cover_image" id="coverInput" class="iw-drop__input" accept="image/jpeg,image/png,image/webp" aria-describedby="coverHelp coverError">

                    <label for="coverInput" class="iw-drop__prompt" id="coverPrompt">
                        <span class="iw-drop__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="3.500"/><circle cx="9" cy="10" r="1.800"/><path d="m21 16-5.200-5.200a1.500 1.500 0 0 0-2.100 0L5 19.500"/></svg></span>
                        <strong>Drag &amp; drop a cover, or <u>browse</u></strong>
                        <small>JPG, PNG or WebP · up to {{ \App\Services\CourseCoverService::MAX_KILOBYTES / 1024 }} MB</small>
                    </label>

                    <div class="iw-drop__preview" id="coverPreview" hidden>
                        <img id="coverImg" alt="Selected cover preview">
                        <div class="iw-drop__bar">
                            <span class="iw-drop__file"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.500 4.500 4.500L19 7.500"/></svg><span id="coverName">Cover image</span></span>
                            <span class="iw-drop__actions">
                                <button type="button" class="iw-drop__btn" id="coverReplace"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12a8 8 0 0 1 14-5.300L20 9M20 4v5h-5M20 12a8 8 0 0 1-14 5.300L4 15M4 20v-5h5"/></svg>Replace</button>
                                <button type="button" class="iw-drop__btn iw-drop__btn--danger" id="coverRemove"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-12M9 7V4h6v3"/></svg>Remove</button>
                            </span>
                        </div>
                    </div>

                    <p class="iw-drop__removed" id="coverRemoved" hidden>The current cover will be removed when you save.</p>
                </div>
                <input type="hidden" name="remove_cover" id="removeCover" value="0">

                <p class="iw-hint" id="coverHelp">16:9 works best · at least {{ \App\Services\CourseCoverService::MIN_WIDTH }}×{{ \App\Services\CourseCoverService::MIN_HEIGHT }} px. Without a cover, we create one for you.</p>
                <p class="iw-field-error" id="coverError" role="alert" @if(! $errors->has('cover_image')) hidden @endif>{{ $errors->first('cover_image') }}</p>
            </div>
        </section>

        {{-- ============================================================ 2 · subject --}}
        <section class="iw-section">
            <span class="iw-section__no">2</span>
            <div class="iw-section__body">
                <h2>University subject</h2>

                @if($locked)
                    <dl class="iw-locked">
                        <div class="iw-locked__wide"><dt>Course title</dt><dd>{{ $course->title }}</dd></div>
                        <div><dt>College</dt><dd>{{ $subject->college_name ?? '—' }}</dd></div>
                        <div><dt>Department</dt><dd>{{ $subject->department_name ?? '—' }}</dd></div>
                        <div><dt>Subject</dt><dd>{{ $subject->course_name ?? '—' }}</dd></div>
                        <div><dt>Topic</dt><dd>{{ $topicName ?? 'General / not specified' }}</dd></div>
                    </dl>
                    <p class="iw-lock-note"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="9" rx="2.500"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>Approved by the administration. To change these, create a new course request.</p>
                @else
                    <div class="iw-fields">
                        @foreach([['college', 'College', 'Select a college', 'Search colleges…'], ['department', 'Department', 'Select a college first', 'Search departments…'], ['subject', 'Subject', 'Select a department first', 'Search by name or code…']] as [$key, $label, $placeholder, $searchHint])
                            <div class="iw-field" data-field="{{ $key }}">
                                <label for="{{ $key }}Toggle">{{ $label }} <em>*</em></label>
                                <div class="iw-combo" id="{{ $key }}Dropdown">
                                    <button type="button" id="{{ $key }}Toggle" class="iw-combo__toggle" aria-haspopup="listbox" aria-expanded="false" aria-controls="{{ $key }}Panel" @if($key !== 'college') disabled @endif>
                                        <span id="{{ $key }}Label" class="iw-combo__label">{{ $placeholder }}</span>
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                                    </button>
                                    <div class="iw-combo__panel" id="{{ $key }}Panel" hidden>
                                        <input type="search" id="{{ $key }}Search" class="iw-combo__search" aria-label="{{ $searchHint }}" placeholder="{{ $searchHint }}" autocomplete="off">
                                        <div class="iw-combo__options" id="{{ $key }}Options" role="listbox"></div>
                                    </div>
                                </div>
                                @if($key === 'college') <input type="hidden" id="college_id">
                                @elseif($key === 'department') <input type="hidden" id="department_id">
                                @else <input type="hidden" name="course_id" id="course_id" value="{{ old('course_id', $course->course_id ?? '') }}"> @endif
                                <p class="iw-field-error" id="{{ $key }}Error" role="alert" @if(! ($key === 'subject' && $errors->has('course_id'))) hidden @endif>{{ $key === 'subject' ? $errors->first('course_id') : '' }}</p>
                            </div>
                        @endforeach

                        <div class="iw-field">
                            <label for="topic_id">Topic <span class="iw-opt">optional</span></label>
                            <select name="topic_id" id="topic_id" class="iw-select">
                                <option value="">General / not specified</option>
                                @foreach($topics as $topic)
                                    <option value="{{ $topic->topic_id }}" data-college="{{ $topic->college_code }}" @selected(old('topic_id', $course->topic_id ?? '') === $topic->topic_id)>{{ $topic->topic_name }}</option>
                                @endforeach
                            </select>
                            <p class="iw-field-error" role="alert" @if(! $errors->has('topic_id')) hidden @endif>{{ $errors->first('topic_id') }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        {{-- ============================================================ 3 · details --}}
        <section class="iw-section">
            <span class="iw-section__no">3</span>
            <div class="iw-section__body">
                <h2>Course details</h2>

                <div class="iw-fields iw-fields--single">
                    @unless($locked)
                        <div class="iw-field">
                            <label for="title">Course title <em>*</em></label>
                            <input type="text" name="title" id="title" class="iw-input" maxlength="255" required placeholder="Example: Java Programming Basics" value="{{ old('title', $course->title ?? '') }}">
                            <p class="iw-field-error" id="titleError" role="alert" @if(! $errors->has('title')) hidden @endif>{{ $errors->first('title') }}</p>
                        </div>
                    @endunless

                    <div class="iw-field">
                        <label for="description">Description <span class="iw-opt">optional</span></label>
                        <textarea name="description" id="description" class="iw-input iw-textarea" rows="5" maxlength="5000" placeholder="What will students learn? Who is it for?">{{ old('description', $course->description ?? '') }}</textarea>
                        <p class="iw-hint iw-hint--end"><span id="descCount">0</span> / 5000</p>
                        <p class="iw-field-error" role="alert" @if(! $errors->has('description')) hidden @endif>{{ $errors->first('description') }}</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ============================================================ 4 · time --}}
        <section class="iw-section">
            <span class="iw-section__no">4</span>
            <div class="iw-section__body">
                <h2>Duration &amp; schedule <span class="iw-opt">optional</span></h2>

                <div class="iw-fields">
                    <div class="iw-field">
                        <span class="iw-label">Total duration</span>
                        <div class="iw-duration">
                            <label class="iw-num"><input type="number" id="durationHours" min="0" max="9999" step="1" placeholder="0" inputmode="numeric"><span>hours</span></label>
                            <label class="iw-num"><input type="number" id="durationMinutes" min="0" max="59" step="1" value="0" inputmode="numeric"><span>min</span></label>
                        </div>
                        <input type="hidden" name="duration_hours" id="duration_hours" value="{{ old('duration_hours', $course->duration_hours ?? '') }}">
                        <p class="iw-hint" id="durationPreview">Duration not specified.</p>
                        <p class="iw-field-error" id="durationError" role="alert" @if(! $errors->has('duration_hours')) hidden @endif>{{ $errors->first('duration_hours') }}</p>
                    </div>

                    <div class="iw-field">
                        <label for="lectureTime">Start time</label>
                        <input type="time" id="lectureTime" class="iw-input">
                    </div>

                    <div class="iw-field iw-field--wide">
                        <span class="iw-label" id="daysLabel">Lecture days</span>
                        <div class="iw-days" role="group" aria-labelledby="daysLabel">
                            @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day)
                                <label class="iw-day"><input type="checkbox" class="lectureDay" value="{{ $day }}"><span><b>{{ substr($day, 0, 3) }}</b><small>{{ $day }}</small></span></label>
                            @endforeach
                        </div>
                        <input type="hidden" name="lecture_schedule" id="lecture_schedule" value="{{ old('lecture_schedule', $course->lecture_schedule ?? '') }}">
                        <p class="iw-hint" id="schedulePreview">No lecture schedule selected.</p>
                        <p class="iw-field-error" id="scheduleError" role="alert" hidden></p>
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- ================================================================ preview + submit --}}
    <aside class="iw-form__aside">
        <div class="iw-aside reveal" style="--i:2">
            <span class="iw-aside__label"><i aria-hidden="true"></i>Live preview</span>

            <div class="iw-card iw-card--preview zu-cover-host">
                <div class="iw-card__media" id="previewMedia">
                    <x-course-cover :course="$previewCourse" variant="card" />
                    <img class="iw-preview__img" id="previewImg" alt="" @if($coverUrl) src="{{ $coverUrl }}" @else hidden @endif>
                    <span class="iw-glass-chip iw-subject"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5.500A1.500 1.500 0 0 1 5.500 4H20v14H5.500A1.500 1.500 0 0 0 4 19.500z"/><path d="M4 19.500A1.500 1.500 0 0 0 5.500 21H20"/></svg><span id="previewSubject">{{ $subject->course_name ?? 'University subject' }}</span></span>
                    <span class="iw-glass-chip iw-state"><i aria-hidden="true"></i><span>{{ $mode === 'edit' ? ($course->course_status === 'completed' ? 'Completed' : 'Ongoing') : 'Awaiting approval' }}</span></span>
                </div>
                <div class="iw-card__body">
                    <h3 class="iw-card__title" id="previewTitle">{{ old('title', $course->title ?? '') ?: 'Your course title' }}</h3>
                    <p class="iw-card__desc {{ $previewDesc === '' ? 'is-placeholder' : '' }}" id="previewDesc">{{ $previewDesc !== '' ? $previewDesc : 'Explore this course and develop your skills.' }}</p>
                    <p class="iw-card__meta"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3.500"/><path d="M2.500 20c.4-3.600 2.800-5.500 6.500-5.500s6.100 1.900 6.500 5.500M16 5a3.500 3.500 0 0 1 0 6.500M18 14.800c1.900.7 3.100 2.400 3.500 5.200"/></svg><span><b>{{ $course->students_count ?? 0 }}</b> students enrolled</span></p>
                </div>
            </div>

            <div class="iw-submit">
                <button type="submit" class="btn {{ $mode === 'edit' ? 'btn-green' : 'btn-blue' }} btn-lg" id="submitBtn">
                    <svg viewBox="0 0 24 24" aria-hidden="true">@if($mode === 'edit')<path d="m5 12.500 4.500 4.500L19 7.500"/>@else<path d="M21 3 10 14M21 3l-7 18-4-7-7-4z"/>@endif</svg>
                    {{ $submitLabel }}
                </button>
                <a href="{{ $leaveUrl }}" class="iw-cancel">Cancel</a>
                @if($mode !== 'edit')<p class="iw-note">The administration reviews it before students can see it.</p>@endif
            </div>
        </div>
    </aside>
</form>

<script id="subjectsData" type="application/json">{!! json_encode($subjects, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endsection

@push('scripts')
<script src="{{ asset('js/zu-course-form.js') }}?v={{ @filemtime(public_path('js/zu-course-form.js')) }}"></script>
@endpush
