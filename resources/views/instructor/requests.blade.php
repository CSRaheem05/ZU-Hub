@extends('layouts.glass')
@section('title', 'Course Requests')
@section('footer_right', 'Zarqa University · Instructor workspace')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/zu-instructor.css') }}?v={{ @filemtime(public_path('css/zu-instructor.css')) }}">
@endpush

@section('content')
@php
    use Carbon\Carbon;

    // What each approval status means for the instructor.
    $meaning = [
        'pending'           => 'Waiting for the administration to review it.',
        'changes_requested' => 'The administration asked for changes. Update the course and resubmit it.',
        'approved'          => 'Approved. It is live for students and managed from My Courses.',
        'rejected'          => 'This request was not approved.',
    ];
    $tabs = ['all' => 'All', 'pending' => 'Pending', 'changes_requested' => 'Changes requested', 'approved' => 'Approved', 'rejected' => 'Rejected'];
    $counts = ['all' => $requests->count()];
    foreach (array_keys($meaning) as $key) { $counts[$key] = $requests->where('approval_status', $key)->count(); }
@endphp

{{-- ================================================================ header --}}
<section class="iw-hero iw-hero--compact reveal" style="--i:0">
    @include('instructor.partials.hero-art')
    <div class="iw-hero__inner">
        <div class="iw-hero__copy">
            <span class="eyebrow">Instructor workspace</span>
            <h1>Course requests</h1>
            <p>Follow every course you have sent for approval, read the administration’s feedback and resubmit when changes are requested.</p>
        </div>
        <div class="iw-hero__actions">
            <a href="{{ route('instructor.courses') }}" class="iw-cta iw-cta--glass">
                <span class="iw-cta__icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5.500A1.500 1.500 0 0 1 5.500 4H20v14H5.500A1.500 1.500 0 0 0 4 19.500z"/><path d="M4 19.500A1.500 1.500 0 0 0 5.500 21H20"/></svg></span>
                My courses
            </a>
            <a href="{{ route('instructor.courses.create') }}" class="iw-cta iw-cta--primary">
                <span class="iw-cta__icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg></span>
                Create course
            </a>
        </div>
    </div>
</section>

@if($requests->isEmpty())

    <section class="iw-empty reveal" style="--i:1">
        <x-course-cover :course="['offering_id' => 'first-request', 'title' => 'Your first request']" variant="preview" />
        <h2>No requests yet</h2>
        <p>Every new course starts as a request. Send one to the administration and you can follow it here.</p>
        <a href="{{ route('instructor.courses.create') }}" class="iw-cta iw-cta--primary">
            <span class="iw-cta__icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg></span>
            Create a course
        </a>
    </section>

@else

    {{-- ================================================================ filter --}}
    <section class="iw-toolbar reveal" style="--i:1" aria-label="Filter requests">
        <div class="zu-nav zu-nav--tabs lg iw-tabs" data-zu-nav role="group" aria-label="Filter by approval status">
            @foreach($tabs as $key => $label)
                @continue($key !== 'all' && $counts[$key] === 0)
                <button type="button" class="zu-nav-link {{ $key === 'all' ? 'is-active' : '' }}" data-filter="{{ $key }}" aria-pressed="{{ $key === 'all' ? 'true' : 'false' }}"><span>{{ $label }}</span><b class="count">{{ $counts[$key] }}</b></button>
            @endforeach
        </div>
        <p class="iw-count" id="requestCount" role="status" aria-live="polite"></p>
    </section>

    {{-- ================================================================ requests --}}
    <div class="iw-req-list" id="requestList">
        @foreach($requests as $req)
            @php $status = $req->approval_status; @endphp

            <article class="iw-req iw-req--{{ $status }}" style="--i:{{ min($loop->index, 10) }}" data-status="{{ $status }}">
                <div class="iw-req__cover"><x-course-cover :course="$req" variant="thumb" /></div>

                <div class="iw-req__main">
                    <h2 class="iw-req__title">{{ $req->title }}</h2>
                    <div class="iw-req__meta">
                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5.500A1.500 1.500 0 0 1 5.500 4H20v14H5.500A1.500 1.500 0 0 0 4 19.500z"/><path d="M4 19.500A1.500 1.500 0 0 0 5.500 21H20"/></svg>{{ $req->course_name }}</span>
                        @if($req->topic_name)<span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9h16M4 15h16M10 3 8 21M16 3l-2 18"/></svg>{{ $req->topic_name }}</span>@endif
                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.500" y="5" width="17" height="15.500" rx="3"/><path d="M8 3v4M16 3v4M3.500 10h17"/></svg>Submitted {{ $req->created_at ? Carbon::parse($req->created_at)->format('M d, Y') : '—' }}</span>
                    </div>
                    <div class="iw-req__status">
                        <span class="status-badge status-{{ $status }}"><span class="status-dot"></span>{{ $tabs[$status] ?? ucfirst($status) }}</span>
                        <span class="iw-req__hint">{{ $meaning[$status] ?? '' }}</span>
                    </div>
                </div>

                <div class="iw-req__action">
                    @if($status === 'changes_requested')
                        <a class="btn btn-yellow" href="{{ route('instructor.courses.edit', $req->offering_id) }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4L19 9a2.800 2.800 0 0 0-4-4L4 16z"/><path d="m13.500 6.500 4 4"/></svg>Edit &amp; resubmit</a>
                    @elseif($status === 'approved')
                        <a class="btn btn-blue" href="{{ route('instructor.courses') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4.500" width="18" height="13" rx="3"/><path d="m10.500 9 4 2.500-4 2.500zM8 21h8"/></svg>Manage course</a>
                    @elseif($status === 'pending')
                        <span class="iw-req__wait"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>Awaiting review</span>
                    @else
                        <a class="btn btn-glass" href="{{ route('instructor.courses.create') }}">Create a new request</a>
                    @endif
                </div>

                @if($req->review_note && in_array($status, ['changes_requested', 'rejected'], true))
                    <div class="iw-feedback">
                        <span class="iw-feedback__label"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.600 7.100L4 20l1-4.800A8 8 0 1 1 21 12z"/></svg>Administration feedback</span>
                        <p>{{ $req->review_note }}</p>
                        @if($req->reviewed_at)<small>Reviewed {{ Carbon::parse($req->reviewed_at)->diffForHumans() }}</small>@endif
                    </div>
                @elseif($req->review_note && $status === 'approved')
                    <div class="iw-feedback">
                        <span class="iw-feedback__label"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.600 7.100L4 20l1-4.800A8 8 0 1 1 21 12z"/></svg>Note from the administration</span>
                        <p>{{ $req->review_note }}</p>
                    </div>
                @endif
            </article>
        @endforeach
    </div>

    <section class="iw-empty" id="noRequests" hidden>
        <h2>Nothing here</h2>
        <p>No requests match this filter.</p>
    </section>

@endif
@endsection

@push('scripts')
<script>
(function () {
    var cards = [].slice.call(document.querySelectorAll('#requestList .iw-req'));
    var tabs = [].slice.call(document.querySelectorAll('[data-filter]'));
    var count = document.getElementById('requestCount');
    var empty = document.getElementById('noRequests');
    if (!cards.length) return;

    function apply(status) {
        var shown = 0;
        cards.forEach(function (c) {
            var on = status === 'all' || c.dataset.status === status;
            c.hidden = !on; if (on) shown++;
        });
        if (count) count.textContent = shown + (shown === 1 ? ' request' : ' requests');
        if (empty) empty.hidden = shown !== 0;
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { var on = t === tab; t.classList.toggle('is-active', on); t.setAttribute('aria-pressed', on ? 'true' : 'false'); });
            apply(tab.getAttribute('data-filter'));
        });
    });
    apply('all');
})();
</script>
@endpush
