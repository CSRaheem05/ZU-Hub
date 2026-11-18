@extends('layouts.glass')
@section('title', 'Courses')
@section('footer_right', 'Zarqa University')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/zu-courses.css') }}?v={{ @filemtime(public_path('css/zu-courses.css')) }}">
@endpush

@section('content')
@php
    use Illuminate\Support\Str;

    $isStudent = auth()->user()->role === 'student';
    $link = fn (array $over = []) => route('courses.index', array_filter(
        array_merge(['college' => $college, 'topic' => $topic, 'status' => $status, 'q' => $search], $over),
        fn ($v) => $v !== null && $v !== ''
    ));
    $shown = $courses->where('visible', true)->count();
    $ordered = $colleges->sortBy([fn ($a, $b) => ((int) $a->college_code <=> (int) $b->college_code) ?: strcmp($a->college_code, $b->college_code)])->values();   // numeric order of the real college_code (codes keep their leading zeros)
    $collegeName = optional($colleges->firstWhere('college_code', $college))->college_name;
    $topicsHere = $college !== '' ? ($topicsByCollege[$college] ?? []) : [];
    $topicName = collect($topicsHere)->firstWhere('id', $topic)['name'] ?? null;
    $statusCount = fn ($s) => $courses->filter(fn ($c) => (! $college || $c->college_code === $college) && (! $topic || $c->topic_id === $topic) && ($s === '' || $c->course_status === $s))->count();
    $collegeCount = fn ($code) => $courses->filter(fn ($c) => $c->college_code === $code && (! $status || $c->course_status === $status))->count();
    $hint = ['' => '', 'ongoing' => 'Ongoing classes: enroll to open the lessons.', 'completed' => 'Completed courses: watch any time, and save them to My Courses.'][$status];

    // "Ongoing courses in <college>" / "<topic> in <college>" (names are isolated so Arabic and English mix cleanly)
    $word = ['' => '', 'ongoing' => 'Ongoing ', 'completed' => 'Completed '][$status];
    $resultTitle = $topicName
        ? '<bdi>'.e($topicName).'</bdi> in <bdi>'.e($collegeName).'</bdi>'
        : ($collegeName ? ($word ? $word.'courses' : 'Courses').' in <bdi>'.e($collegeName).'</bdi>' : ($word ? $word.'courses' : 'All courses'));
    $vi = 0;   // position among the visible cards (drives the entrance stagger)

    // stored decimal hours -> "5h 30m"
    $duration = function ($h) {
        $m = (int) round(((float) $h) * 60);
        if ($m <= 0) return null;
        [$hh, $mm] = [intdiv($m, 60), $m % 60];
        return [($hh ? $hh.'h' : '').($hh && $mm ? ' ' : '').($mm ? $mm.'m' : ''), ($hh ? $hh.' '.Str::plural('hour', $hh) : '').($hh && $mm ? ' ' : '').($mm ? $mm.' '.Str::plural('minute', $mm) : '')];
    };

    $icons = [
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.6-3.6"/>',
        'x' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'check' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'book' => '<path d="M4 5.5A1.5 1.5 0 0 1 5.5 4H20v14H5.5A1.5 1.5 0 0 0 4 19.5z"/><path d="M4 19.5A1.5 1.5 0 0 0 5.5 21H20"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'play' => '<path d="M8 5.5v13l11-6.5z"/>',
        'compass' => '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5-5 2 2-5z"/>',
        'grid' => '<rect x="4" y="4" width="6.5" height="6.5" rx="1.6"/><rect x="13.5" y="4" width="6.5" height="6.5" rx="1.6"/><rect x="4" y="13.5" width="6.5" height="6.5" rx="1.6"/><rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1.6"/>',
        'chevron' => '<path d="m6 9 6 6 6-6"/>',
        'tag' => '<path d="M3 12V4h8l9 9-8 8z"/><circle cx="7.5" cy="8.5" r="1.2"/>',
    ];
    $svg = fn ($n, $c = '') => '<svg class="'.$c.'" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$n].'</svg>';
@endphp

<div class="cx" id="cx"
     data-url="{{ route('courses.index') }}"
     data-college="{{ $college }}" data-topic="{{ $topic }}" data-status="{{ $status }}" data-q="{{ $search }}">

    {{-- ================================================================== 01 · heading + progressive pickers --}}
    <header class="cx-head">
        <h1 class="cx-rv" style="--rv:0">What will you <em>learn<svg viewBox="0 0 120 12" preserveAspectRatio="none" aria-hidden="true"><path d="M2 8C22 2 34 12 58 6s40-5 60 0"/></svg></em> next?</h1>
        <p class="cx-sub cx-rv" style="--rv:1">Choose a college, narrow it down by topic, then enroll or start watching.</p>

        <div class="cx-pickers cx-rv" style="--rv:2" id="cxPickers">
            {{-- step 1: college (a custom searchable list; plain links, so it also works without JavaScript) --}}
            <div class="cx-picker" id="cxPickCollege">
                <details class="cx-combo" id="cxComboCollege">
                    <summary class="cx-combo__btn" aria-label="Choose a college">
                        <span class="cx-combo__no {{ $college !== '' ? 'is-set' : '' }}" id="cxCollegeNo">@if($college !== ''){{ $college }}@else{!! $svg('grid') !!}@endif</span>
                        <span class="cx-combo__txt"><small>College</small><strong id="cxCollegeName" dir="auto">{{ $collegeName ?? 'All colleges' }}</strong></span>
                        {!! $svg('chevron', 'cx-combo__chev') !!}
                    </summary>
                    <div class="cx-combo__panel">
                        <label class="cx-combo__search">{!! $svg('search') !!}<input type="search" id="cxCollegeSearch" placeholder="Search colleges" aria-label="Search colleges" autocomplete="off"></label>
                        <ul class="cx-combo__list" id="cxCollegeList">
                            <li><a href="{{ $link(['college' => null, 'topic' => null]) }}" class="cx-opt {{ $college === '' ? 'is-active' : '' }}" data-college="" data-name="all colleges" @if($college === '') aria-current="true" @endif><span class="cx-opt__no">{!! $svg('grid') !!}</span><span class="cx-opt__name" dir="auto">All colleges</span><b data-ccount="">{{ $courses->filter(fn ($c) => ! $status || $c->course_status === $status)->count() }}</b></a></li>
                            @foreach($ordered as $co)
                                @php $n = $collegeCount($co->college_code); @endphp
                                <li><a href="{{ $link(['college' => $co->college_code, 'topic' => null]) }}" class="cx-opt {{ $college === $co->college_code ? 'is-active' : '' }} {{ $n === 0 ? 'is-zero' : '' }}" data-college="{{ $co->college_code }}" data-name="{{ mb_strtolower($co->college_name.' '.$co->college_code) }}" @if($college === $co->college_code) aria-current="true" @endif><span class="cx-opt__no">{{ $co->college_code }}</span><span class="cx-opt__name" dir="auto">{{ $co->college_name }}</span><b data-ccount="{{ $co->college_code }}">{{ $n }}</b></a></li>
                            @endforeach
                        </ul>
                        <p class="cx-combo__none" id="cxCollegeNone" hidden>No college matches your search.</p>
                    </div>
                </details>
            </div>

            {{-- step 2: topic. Only exists once a college is chosen, and only lists that college's real topics --}}
            <div class="cx-picker cx-picker--topic" id="cxPickTopic" @if($college === '') hidden @endif>
                {!! $svg('chevron', 'cx-picker__join') !!}
                <div class="cx-picker__slot" id="cxTopicSlot">
                    @if(! empty($topicsHere))
                        <details class="cx-combo" id="cxComboTopic">
                            <summary class="cx-combo__btn" aria-label="Choose a topic">
                                <span class="cx-combo__no {{ $topic !== '' ? 'is-set' : '' }}">{!! $svg('tag') !!}</span>
                                <span class="cx-combo__txt"><small>Topic</small><strong dir="auto">{{ $topicName ?? 'All topics' }}</strong></span>
                                {!! $svg('chevron', 'cx-combo__chev') !!}
                            </summary>
                            <div class="cx-combo__panel">
                                <ul class="cx-combo__list">
                                    <li><a href="{{ $link(['topic' => null]) }}" class="cx-opt {{ $topic === '' ? 'is-active' : '' }}" data-topic=""><span class="cx-opt__no">{!! $svg('tag') !!}</span><span class="cx-opt__name">All topics</span></a></li>
                                    @foreach($topicsHere as $t)
                                        <li><a href="{{ $link(['topic' => $t['id']]) }}" class="cx-opt {{ $topic === $t['id'] ? 'is-active' : '' }}" data-topic="{{ $t['id'] }}" data-name="{{ mb_strtolower($t['name']) }}"><span class="cx-opt__no">{!! $svg('tag') !!}</span><span class="cx-opt__name" dir="auto">{{ $t['name'] }}</span></a></li>
                                    @endforeach
                                </ul>
                            </div>
                        </details>
                    @else
                        <p class="cx-picker__none">{!! $svg('tag') !!}<span>No topics for this college yet. Showing all of its courses.</span></p>
                    @endif
                </div>
            </div>
        </div>
    </header>

    {{-- ================================================================== floating dock: search + status --}}
    <div class="cx-dock cx-rv" style="--rv:3">
        <form class="cx-search" role="search" action="{{ route('courses.index') }}" method="get" id="cxSearchForm">
            @foreach(['college' => $college, 'topic' => $topic, 'status' => $status] as $k => $v)
                @if($v !== '')<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
            @endforeach
            {!! $svg('search', 'cx-search__icon') !!}
            <input type="search" name="q" id="cxSearch" value="{{ $search }}" maxlength="80" autocomplete="off" placeholder="Search courses, subjects, instructors" aria-label="Search courses">
            <button type="button" class="cx-search__clear" id="cxSearchClear" aria-label="Clear search" @if($search === '') hidden @endif>{!! $svg('x') !!}</button>
        </form>

        <nav class="cx-seg" id="cxSeg" aria-label="Course status">
            <span class="cx-seg__thumb" aria-hidden="true"></span>
            @foreach(['' => 'All', 'ongoing' => 'Ongoing', 'completed' => 'Completed'] as $key => $label)
                <a href="{{ $link(['status' => $key]) }}" data-status="{{ $key }}" class="cx-seg__item {{ $status === $key ? 'is-active' : '' }}" @if($status === $key) aria-current="true" @endif>
                    {{ $label }}<b data-count="{{ $key }}">{{ $statusCount($key) }}</b>
                </a>
            @endforeach
        </nav>
    </div>

    {{-- ================================================================== 02 · path + topics, 03 · courses --}}
    <section class="cx-main" aria-label="Courses">

        <div class="cx-path cx-rv" style="--rv:4">
            <h2 class="cx-result" id="cxResult">{!! $resultTitle !!}</h2>
            <p class="cx-count" role="status" aria-live="polite"><b id="cxCount">{{ $shown }}</b> <span id="cxCountLabel">{{ Str::plural('course', $shown) }}</span></p>
            <a href="{{ route('courses.index') }}" class="cx-reset" id="cxReset" @if(! $college && ! $topic && ! $status && $search === '') hidden @endif>Reset</a>
        </div>
        <p class="cx-hint cx-rv" style="--rv:4" id="cxHint" @if($hint === '') hidden @endif>{{ $hint }}</p>

        <div class="cx-grid" id="cxGrid">
            @foreach($courses as $c)
                @php
                    $done = $c->course_status === 'completed';
                    $enrolled = $enrolledOfferingIds->contains($c->offering_id);
                    $open = $isStudent && ($done || $enrolled);
                    $href = $open ? route('student.course.show', $c->offering_id) : null;
                    $desc = trim((string) $c->description) ?: 'Explore this course and build your skills.';
                    $dur = $duration($c->duration_hours);
                    $ci = $c->visible ? $vi++ : 0;
                @endphp
                <article class="cx-card {{ $done ? 'is-done' : 'is-live' }} cx-rv cx-rv--card" style="--ci:{{ $ci }}" @if(! $c->visible) hidden @endif
                         data-id="{{ $c->offering_id }}" data-college="{{ $c->college_code }}" data-topic="{{ $c->topic_id }}" data-status="{{ $c->course_status }}" data-search="{{ $c->search }}">

                    <div class="cx-card__media">
                        <x-course-cover :course="$c" variant="card" />
                        <span class="cx-badge cx-badge--status">@if($done){!! $svg('check') !!}Completed @else<i aria-hidden="true"></i>Ongoing @endif</span>
                        @if($dur)<span class="cx-badge cx-badge--dur" title="Course length: {{ $dur[1] }}">{!! $svg('clock') !!}<span aria-hidden="true">{{ $dur[0] }}</span><span class="sr-only">{{ $dur[1] }}</span></span>@endif
                    </div>

                    <div class="cx-card__body">
                        <p class="cx-card__subject" title="{{ $c->course_name }}">{!! $svg('book') !!}<span dir="auto">{{ $c->course_name }}</span></p>
                        <h3 class="cx-card__title" dir="auto">@if($href)<a href="{{ $href }}">{{ $c->title }}</a>@else{{ $c->title }}@endif</h3>
                        <p class="cx-card__desc" dir="auto">{{ $desc }}</p>

                        <div class="cx-card__foot">
                            <p class="cx-card__by">
                                <span class="cx-avatar" aria-hidden="true">{{ Str::upper(Str::substr($c->instructor_name, 0, 1)) }}</span>
                                <span class="cx-card__who">
                                    <strong dir="auto" title="Instructor">{{ $c->instructor_name }}</strong>
                                    <small><bdi>{{ $c->college_name }}</bdi>@if($c->topic_name)<i aria-hidden="true">·</i><bdi>{{ $c->topic_name }}</bdi>@endif</small>
                                </span>
                            </p>

                            @if($isStudent)
                                @if($done)
                                    <a class="cx-cta" href="{{ $href }}">Watch course{!! $svg('play') !!}</a>
                                @elseif($enrolled)
                                    <a class="cx-cta" href="{{ $href }}">Continue{!! $svg('arrow') !!}</a>
                                @else
                                    <form method="post" action="{{ route('courses.enroll', $c->offering_id) }}">@csrf
                                        <button class="cx-cta" aria-label="Enroll in “{{ $c->title }}”">Enroll{!! $svg('arrow') !!}</button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="cx-empty" id="cxEmpty" @if($shown > 0) hidden @endif>
            <span class="cx-empty__art" aria-hidden="true">{!! $svg('compass') !!}</span>
            <h2 id="cxEmptyTitle">{{ $courses->isEmpty() ? 'No courses are open yet' : 'Nothing matches those filters' }}</h2>
            <p id="cxEmptyText">{{ $courses->isEmpty() ? 'Approved courses will appear here as soon as instructors publish them.' : 'Try another college or topic, or clear the search.' }}</p>
            <div class="cx-empty__actions" id="cxEmptyActions">
                @if($courses->isNotEmpty())<a href="{{ route('courses.index') }}" class="cx-cta" data-reset>Reset filters</a>@endif
            </div>
        </div>
    </section>


    <script type="application/json" id="cxData">@json(['topics' => (object) $topicsByCollege, 'colleges' => $colleges->pluck('college_name', 'college_code')])</script>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/zu-courses.js') }}?v={{ @filemtime(public_path('js/zu-courses.js')) }}"></script>
@endpush
