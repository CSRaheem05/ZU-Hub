@extends('layouts.glass')
@section('title', 'My Courses')
@section('footer_right', 'Zarqa University · Instructor workspace')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/zu-instructor.css') }}?v={{ @filemtime(public_path('css/zu-instructor.css')) }}">
@endpush

@section('content')
@php use Illuminate\Support\Str; @endphp

{{-- ================================================================ header --}}
<section class="iw-hero reveal" style="--i:0">
    @include('instructor.partials.hero-art')
    <div class="iw-hero__inner">
        <div class="iw-hero__copy">
            <span class="eyebrow">Instructor workspace</span>
            <h1>My Courses</h1>
            <p>Your approved courses in one place. Manage lessons, follow your students and keep every course up to date.</p>
        </div>

        <div class="iw-hero__actions">
            <a href="{{ route('instructor.requests') }}" class="iw-cta iw-cta--glass">
                <span class="iw-cta__icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3h8l4 4v13a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M16 3v4h4M10 12h6M10 16h4"/></svg></span>
                Course requests
                @if($needsChanges > 0)<b class="iw-cta__badge">{{ $needsChanges }} {{ $needsChanges === 1 ? 'needs' : 'need' }} changes</b>
                @elseif($pendingRequests > 0)<b class="iw-cta__badge">{{ $pendingRequests }} pending</b>@endif
            </a>
            <a href="{{ route('instructor.courses.create') }}" class="iw-cta iw-cta--primary">
                <span class="iw-cta__icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg></span>
                Create course
            </a>
        </div>
    </div>
</section>

@if($courses->isEmpty())

    {{-- ================================================================ empty --}}
    <section class="iw-empty reveal" style="--i:1">
        <x-course-cover :course="['offering_id' => 'welcome-to-zu-hub', 'title' => 'Your first course']" variant="preview" />
        <h2>No courses yet</h2>
        <p>Create your first course and send it for approval. Once it is approved it appears here and students can find it.</p>
        <a href="{{ route('instructor.courses.create') }}" class="iw-cta iw-cta--primary">
            <span class="iw-cta__icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg></span>
            Create your first course
        </a>
    </section>

@else

    {{-- ================================================================ search + filter --}}
    <section class="iw-toolbar reveal" style="--i:1" aria-label="Find your courses">
        <label class="iw-search" for="courseSearch">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.500-3.500"/></svg>
            <span class="iw-sr">Search courses</span>
            <input type="search" id="courseSearch" placeholder="Search by title, subject or description…" autocomplete="off">
            <button type="button" class="iw-search__clear" id="courseSearchClear" aria-label="Clear search" hidden><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
        </label>

        <div class="zu-nav zu-nav--tabs lg iw-tabs" data-zu-nav role="group" aria-label="Filter by course status">
            <button type="button" class="zu-nav-link is-active" data-filter="all" aria-pressed="true"><span>All</span></button>
            <button type="button" class="zu-nav-link" data-filter="ongoing" aria-pressed="false"><span>Ongoing</span></button>
            <button type="button" class="zu-nav-link" data-filter="completed" aria-pressed="false"><span>Completed</span></button>
        </div>

        <p class="iw-count" id="courseResultCount" role="status" aria-live="polite"></p>
    </section>

    {{-- ================================================================ courses --}}
    <div class="iw-grid" id="courseCards">
        @foreach($courses as $course)
            @php
                $completed = $course->course_status === 'completed';
                $desc = trim((string) $course->description); // the same text students see on their cards
            @endphp

            <article class="iw-card zu-cover-host reveal" style="--i:{{ min($loop->index + 2, 9) }}"
                     data-status="{{ $course->course_status }}"
                     data-search="{{ $course->title }} {{ $course->course_name }} {{ $desc }}">

                <div class="iw-card__media">
                    <x-course-cover :course="$course" />

                    <span class="iw-glass-chip iw-subject" title="{{ $course->course_name }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5.500A1.500 1.500 0 0 1 5.500 4H20v14H5.500A1.500 1.500 0 0 0 4 19.500z"/><path d="M4 19.500A1.500 1.500 0 0 0 5.500 21H20"/></svg>
                        <span>{{ $course->course_name }}</span>
                    </span>

                    <span class="iw-glass-chip iw-state {{ $completed ? 'iw-state--completed' : '' }}">
                        @if($completed)<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.500 4.500 4.500L19 7.500"/></svg>@else<i aria-hidden="true"></i>@endif
                        <span>{{ $completed ? 'Completed' : 'Ongoing' }}</span>
                    </span>
                </div>

                <div class="iw-card__body">
                    <h2 class="iw-card__title">{{ $course->title }}</h2>

                    <p class="iw-card__desc {{ $desc === '' ? 'is-placeholder' : '' }}" @if($desc !== '') title="{{ Str::limit($desc, 300) }}" @else title="No description yet: students see this default text. Use Edit to add one." @endif>{{ $desc !== '' ? $desc : 'Explore this course and develop your skills.' }}</p>

                    <p class="iw-card__meta">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3.500"/><path d="M2.500 20c.4-3.600 2.800-5.500 6.500-5.500s6.100 1.900 6.500 5.500M16 5a3.500 3.500 0 0 1 0 6.500M18 14.800c1.900.7 3.100 2.400 3.500 5.200"/></svg>
                        <span><b>{{ $course->students_count }}</b> {{ Str::plural('student', $course->students_count) }} enrolled</span>
                    </p>

                    <div class="iw-actions">
                        <a class="iw-primary" href="{{ route('instructor.lessons.index', $course->offering_id) }}">
                            <svg class="iw-primary__icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4.500" width="18" height="13" rx="3"/><path d="m10.500 9 4 2.500-4 2.500zM8 21h8"/></svg>
                            <span>Manage lessons</span>
                            <svg class="iw-primary__go" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </a>

                        <div class="iw-segment" role="group" aria-label="More for {{ $course->title }}">
                            <a class="iw-seg" href="{{ route('instructor.students', $course->offering_id) }}">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3.500"/><path d="M2.500 20c.4-3.600 2.800-5.500 6.500-5.500s6.100 1.900 6.500 5.500M16 5a3.500 3.500 0 0 1 0 6.500"/></svg>
                                Students
                            </a>
                            <a class="iw-seg" href="{{ route('instructor.courses.edit', $course->offering_id) }}">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4L19 9a2.800 2.800 0 0 0-4-4L4 16z"/><path d="m13.500 6.500 4 4"/></svg>
                                Edit
                            </a>

                            <details class="iw-more">
                                <summary aria-label="More actions for {{ $course->title }}"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="5" cy="12" r="1.800"/><circle cx="12" cy="12" r="1.800"/><circle cx="19" cy="12" r="1.800"/></svg></summary>
                                <div class="iw-more__menu" role="menu">
                                    <form method="POST" action="{{ route('instructor.courses.complete', $course->offering_id) }}" data-submit-once>@csrf @method('PATCH')
                                        @if($completed)
                                            <button type="submit" class="iw-more__item iw-more__item--blue" role="menuitem"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12a8 8 0 0 1 14-5.300L20 9M20 4v5h-5"/></svg>Mark as ongoing</button>
                                        @else
                                            <button type="submit" class="iw-more__item iw-more__item--green" role="menuitem"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12.500 3 3 5-6"/></svg>Mark as completed</button>
                                        @endif
                                    </form>
                                    <hr>
                                    <form method="POST" action="{{ route('instructor.courses.destroy', $course->offering_id) }}">@csrf @method('DELETE')
                                        <button type="submit" class="iw-more__item iw-more__item--red" role="menuitem" data-zu-confirm
                                                data-confirm-tone="red" data-confirm-icon="warning"
                                                data-confirm-title="Delete this course?"
                                                data-confirm-message="“{{ $course->title }}” will be permanently deleted. This cannot be undone."
                                                data-confirm-label="Yes, delete">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-12M9 7V4h6v3"/></svg>Delete course
                                        </button>
                                    </form>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    <section class="iw-empty" id="noSearchResults" hidden>
        <h2>No matching courses</h2>
        <p>Try a different search, or switch the filter back to “All”.</p>
    </section>

@endif

@endsection

@push('scripts')
<script src="{{ asset('js/zu-instructor-courses.js') }}?v={{ @filemtime(public_path('js/zu-instructor-courses.js')) }}"></script>
@endpush
