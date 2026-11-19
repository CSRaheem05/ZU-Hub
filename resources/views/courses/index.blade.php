@extends('layouts.glass')
@section('title', 'Courses')
@section('footer_right', 'Zarqa University')
@section('header_language', '1')

@push('styles')
{{-- apply the saved language before the first paint, so an Arabic reader never sees the page flip --}}
<script>try{if(localStorage.getItem('zu-language')==='ar'){var h=document.documentElement;h.lang='ar';h.dir='rtl';h.classList.add('cx-ar');}}catch(e){}</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="{{ asset('css/zu-courses.css') }}?v={{ @filemtime(public_path('css/zu-courses.css')) }}">
@endpush

@section('content')
@php
    use Illuminate\Support\Carbon;
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
    $vi = 0;   // position among the visible cards (drives the entrance stagger)

    // bilingual text: English is rendered, the page script swaps to Arabic (same data-en / data-ar convention as My Courses)
    $t = fn (string $en, string $ar) => 'data-en="'.e($en).'" data-ar="'.e($ar).'"';

    // stored decimal hours -> ["5h 30m", "5 hours 30 minutes", "5 س 30 د", "5 ساعات و30 دقيقة"]
    $duration = function ($h) {
        $m = (int) round(((float) $h) * 60);
        if ($m <= 0) return null;
        [$hh, $mm] = [intdiv($m, 60), $m % 60];
        $join = fn ($a, $b, $sep = ' ') => trim($a.($a !== '' && $b !== '' ? $sep : '').$b);
        return [
            $join($hh ? $hh.'h' : '', $mm ? $mm.'m' : ''),
            $join($hh ? $hh.' '.Str::plural('hour', $hh) : '', $mm ? $mm.' '.Str::plural('minute', $mm) : ''),
            $join($hh ? $hh.' س' : '', $mm ? $mm.' د' : ''),
            $join($hh ? $hh.' '.($hh === 1 ? 'ساعة' : ($hh === 2 ? 'ساعتان' : ($hh <= 10 ? 'ساعات' : 'ساعة'))) : '', $mm ? $mm.' دقيقة' : '', ' و'),
        ];
    };

    // "Days: Sunday, Tuesday; Time: 19:00" (what the course form stores) -> a week strip; anything else is shown as written
    $week = [
        ['saturday', 'Sat', 'سبت'], ['sunday', 'Sun', 'أحد'], ['monday', 'Mon', 'إثنين'], ['tuesday', 'Tue', 'ثلاثاء'],
        ['wednesday', 'Wed', 'أربعاء'], ['thursday', 'Thu', 'خميس'], ['friday', 'Fri', 'جمعة'],
    ];
    $schedule = function ($s) use ($week) {
        $s = trim((string) $s);
        if ($s === '') return null;
        $days = preg_match('/Days:\s*([^;]+)/i', $s, $m) ? array_map(fn ($d) => mb_strtolower(trim($d)), explode(',', $m[1])) : [];
        $time = preg_match('/Time:\s*(\d{1,2}:\d{2})/', $s, $m) ? $m[1] : null;
        $on = array_values(array_filter($week, fn ($w) => in_array($w[0], $days, true)));
        return ['days' => $days, 'time' => $time, 'raw' => $s, 'count' => count($on),
            'en' => implode(', ', array_map(fn ($w) => ucfirst($w[0]), $on)), 'ar' => implode('، ', array_map(fn ($w) => $w[2], $on))];
    };
    $date = function ($v) {
        if (! $v) return null;
        try { $d = Carbon::parse($v); } catch (\Throwable $e) { return null; }
        return [$d->format('j M Y'), $d->copy()->locale('ar')->translatedFormat('j F Y')];
    };

    $icons = [
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.6-3.6"/>',
        'x' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'check' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'play' => '<path d="M8 5.5v13l11-6.5z"/>',
        'compass' => '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5-5 2 2-5z"/>',
        'grid' => '<rect x="4" y="4" width="6.5" height="6.5" rx="1.6"/><rect x="13.5" y="4" width="6.5" height="6.5" rx="1.6"/><rect x="4" y="13.5" width="6.5" height="6.5" rx="1.6"/><rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1.6"/>',
        'chevron' => '<path d="m6 9 6 6 6-6"/>',
        'tag' => '<path d="M3 12V4h8l9 9-8 8z"/><circle cx="7.5" cy="8.5" r="1.2"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.01"/>',
        'calendar' => '<rect x="4" y="5" width="16" height="15" rx="3"/><path d="M4 10h16M9 3v4M15 3v4"/>',
        'book' => '<path d="M4 5.5A1.5 1.5 0 0 1 5.5 4H20v14H5.5A1.5 1.5 0 0 0 4 19.5z"/><path d="M4 19.5A1.5 1.5 0 0 0 5.5 21H20"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.4-3.6 2.8-5.5 6.5-5.5s6.1 1.9 6.5 5.5"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8M18.5 14.8c1.8.7 2.8 2.4 3 5.2"/>',
        'building' => '<path d="M4 21V6l8-3 8 3v15M9 21v-5h6v5M8 9h.01M12 9h.01M16 9h.01M8 12.5h.01M12 12.5h.01M16 12.5h.01"/>',
        'prev' => '<path d="m15 6-6 6 6 6"/>',
        'next' => '<path d="m9 6 6 6-6 6"/>',
        'sparkle' => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M18 6l-2.5 2.5M8.5 15.5 6 18"/>',
    ];
    $svg = fn ($n, $c = '') => '<svg class="'.$c.'" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$n].'</svg>';

    $totalOngoing = $courses->where('course_status', 'ongoing')->count();
    $teaching = $courses->pluck('college_code')->unique()->count();
    // three real covers for the hero (uploaded images first, then generated covers)
    $heroCourses = $courses->sortByDesc(fn ($c) => $c->cover_image ? 1 : 0)->take(3)->values();
@endphp

<div class="cx" id="cx"
     data-url="{{ route('courses.index') }}"
     data-college="{{ $college }}" data-topic="{{ $topic }}" data-status="{{ $status }}" data-q="{{ $search }}">

    {{-- ================================================================== hero --}}
    <header class="cx-hero cx-in" style="--in:0">
        <div class="cx-hero__text">
            <p class="cx-kicker cx-in" style="--in:1">{!! $svg('sparkle') !!}<span {!! $t('Course catalog · Zarqa University', 'دليل الدورات · جامعة الزرقاء') !!}>Course catalog · Zarqa University</span></p>
            <h1 class="cx-in" style="--in:2"><span {!! $t('What will you', 'ماذا ستتعلّم') !!}>What will you</span> <em><span {!! $t('learn', 'اليوم') !!}>learn</span><svg viewBox="0 0 120 12" preserveAspectRatio="none" aria-hidden="true"><path d="M2 8C22 2 34 12 58 6s40-5 60 0"/></svg></em><span {!! $t(' next?', '؟') !!}> next?</span></h1>
            <p class="cx-sub cx-in" style="--in:3" {!! $t('Search every approved course at Zarqa University, then enroll or start watching.', 'ابحث في كل الدورات المعتمدة في جامعة الزرقاء، ثم سجّل أو ابدأ المشاهدة.') !!}>Search every approved course at Zarqa University, then enroll or start watching.</p>

            <dl class="cx-stats cx-in" style="--in:4">
                <div><dt {!! $t('Courses', 'الدورات') !!}>Courses</dt><dd data-tick="{{ $courses->count() }}">{{ $courses->count() }}</dd></div>
                <div class="is-live"><dt {!! $t('Ongoing', 'جارية') !!}>Ongoing</dt><dd data-tick="{{ $totalOngoing }}">{{ $totalOngoing }}</dd></div>
                <div><dt {!! $t('Completed', 'مكتملة') !!}>Completed</dt><dd data-tick="{{ $courses->count() - $totalOngoing }}">{{ $courses->count() - $totalOngoing }}</dd></div>
                <div><dt {!! $t('Colleges', 'الكليات') !!}>Colleges</dt><dd data-tick="{{ $teaching }}">{{ $teaching }}</dd></div>
            </dl>
        </div>

        @if($heroCourses->isNotEmpty())
            <div class="cx-hero__art" aria-hidden="true">
                @foreach($heroCourses as $i => $hc)
                    <div class="cx-hero__cover" style="--h:{{ $i }}"><x-course-cover :course="$hc" variant="card" alt="" /></div>
                @endforeach
            </div>
        @endif
    </header>

    {{-- ================================================================== search + filters (one calm toolbar) --}}
    <section class="cx-bar cx-in" style="--in:3" aria-label="Find a course" data-aria-en="Find a course" data-aria-ar="ابحث عن دورة">
        <form class="cx-search" role="search" action="{{ route('courses.index') }}" method="get" id="cxSearchForm">
            @foreach(['college' => $college, 'topic' => $topic, 'status' => $status] as $k => $v)
                @if($v !== '')<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
            @endforeach
            <label for="cxSearch" class="sr-only" {!! $t('Search courses', 'ابحث في الدورات') !!}>Search courses</label>
            {!! $svg('search', 'cx-search__icon') !!}
            <input type="search" name="q" id="cxSearch" value="{{ $search }}" maxlength="80" autocomplete="off" placeholder="Search courses, subjects or instructors" data-ph-en="Search courses, subjects or instructors" data-ph-ar="ابحث عن دورة أو مادة أو محاضر">
            <kbd class="cx-search__key" aria-hidden="true">/</kbd>
            <button type="button" class="cx-search__clear" id="cxSearchClear" aria-label="Clear search" data-aria-en="Clear search" data-aria-ar="مسح البحث" @if($search === '') hidden @endif>{!! $svg('x') !!}</button>
        </form>

        {{-- college: a searchable list (a <details> of plain links, so it also works without JavaScript) --}}
        <div class="cx-picker" id="cxPickCollege">
            <details class="cx-combo" id="cxComboCollege">
                <summary class="cx-combo__btn" aria-label="Choose a college">
                    <span class="cx-combo__no {{ $college !== '' ? 'is-set' : '' }}" id="cxCollegeNo">@if($college !== ''){{ $college }}@else{!! $svg('building') !!}@endif</span>
                    <span class="cx-combo__txt"><small {!! $t('College', 'الكلية') !!}>College</small><strong id="cxCollegeName" dir="auto">{{ $collegeName ?? 'All colleges' }}</strong></span>
                    {!! $svg('chevron', 'cx-combo__chev') !!}
                </summary>
                <div class="cx-combo__panel">
                    <label class="cx-combo__search">{!! $svg('search') !!}<input type="search" id="cxCollegeSearch" placeholder="Search colleges" aria-label="Search colleges" data-ph-en="Search colleges" data-ph-ar="ابحث عن كلية" autocomplete="off"></label>
                    <ul class="cx-combo__list" id="cxCollegeList">
                        <li><a href="{{ $link(['college' => null, 'topic' => null]) }}" class="cx-opt {{ $college === '' ? 'is-active' : '' }}" data-college="" data-name="all colleges كل الكليات" @if($college === '') aria-current="true" @endif><span class="cx-opt__no">{!! $svg('grid') !!}</span><span class="cx-opt__name" {!! $t('All colleges', 'كل الكليات') !!}>All colleges</span><b data-ccount="">{{ $courses->filter(fn ($c) => ! $status || $c->course_status === $status)->count() }}</b></a></li>
                        @foreach($ordered as $co)
                            @php $n = $collegeCount($co->college_code); @endphp
                            <li><a href="{{ $link(['college' => $co->college_code, 'topic' => null]) }}" class="cx-opt {{ $college === $co->college_code ? 'is-active' : '' }} {{ $n === 0 ? 'is-zero' : '' }}" data-college="{{ $co->college_code }}" data-name="{{ mb_strtolower($co->college_name.' '.$co->college_code) }}" @if($college === $co->college_code) aria-current="true" @endif><span class="cx-opt__no">{{ $co->college_code }}</span><span class="cx-opt__name" dir="auto">{{ $co->college_name }}</span><b data-ccount="{{ $co->college_code }}">{{ $n }}</b></a></li>
                        @endforeach
                    </ul>
                    <p class="cx-combo__none" id="cxCollegeNone" hidden {!! $t('No college matches your search.', 'لا توجد كلية مطابقة لبحثك.') !!}>No college matches your search.</p>
                </div>
            </details>
        </div>

        {{-- topic: only exists once a college is chosen, and only lists that college's real topics --}}
        <div class="cx-picker cx-picker--topic" id="cxPickTopic" @if($college === '') hidden @endif>
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
                                @foreach($topicsHere as $tp)
                                    <li><a href="{{ $link(['topic' => $tp['id']]) }}" class="cx-opt {{ $topic === $tp['id'] ? 'is-active' : '' }}" data-topic="{{ $tp['id'] }}" data-name="{{ mb_strtolower($tp['name']) }}"><span class="cx-opt__no">{!! $svg('tag') !!}</span><span class="cx-opt__name" dir="auto">{{ $tp['name'] }}</span></a></li>
                                @endforeach
                            </ul>
                        </div>
                    </details>
                @else
                    <p class="cx-picker__none">{!! $svg('tag') !!}<span>No topics yet</span></p>
                @endif
            </div>
        </div>

        <nav class="cx-seg" id="cxSeg" aria-label="Course status" data-aria-en="Course status" data-aria-ar="حالة الدورة">
            @foreach(['' => ['All', 'الكل'], 'ongoing' => ['Ongoing', 'جارية'], 'completed' => ['Completed', 'مكتملة']] as $key => [$en, $ar])
                <a href="{{ $link(['status' => $key]) }}" data-status="{{ $key }}" class="cx-seg__item {{ $status === $key ? 'is-active' : '' }}" @if($status === $key) aria-current="true" @endif>
                    @if($key === 'ongoing')<i class="cx-dot" aria-hidden="true"></i>@elseif($key === 'completed'){!! $svg('check') !!}@endif<span {!! $t($en, $ar) !!}>{{ $en }}</span><b data-count="{{ $key }}">{{ $statusCount($key) }}</b>
                </a>
            @endforeach
        </nav>
    </section>

    {{-- ================================================================== result line: count + active filters --}}
    <div class="cx-result cx-in" style="--in:4">
        <p class="cx-result__count" role="status" aria-live="polite"><b id="cxCount">{{ $shown }}</b> <span id="cxCountLabel">{{ Str::plural('course', $shown) }}</span><span id="cxHint" class="cx-result__hint"></span></p>
        <div class="cx-chips" id="cxChips">
            @if($collegeName)<a class="cx-chip" data-chip="college" href="{{ $link(['college' => null, 'topic' => null]) }}"><bdi>{{ $collegeName }}</bdi>{!! $svg('x') !!}</a>@endif
            @if($topicName)<a class="cx-chip" data-chip="topic" href="{{ $link(['topic' => null]) }}"><bdi>{{ $topicName }}</bdi>{!! $svg('x') !!}</a>@endif
            @if($status)<a class="cx-chip" data-chip="status" href="{{ $link(['status' => null]) }}">{{ ucfirst($status) }}{!! $svg('x') !!}</a>@endif
            @if($search !== '')<a class="cx-chip" data-chip="q" href="{{ $link(['q' => null]) }}">“{{ $search }}”{!! $svg('x') !!}</a>@endif
            <a href="{{ route('courses.index') }}" class="cx-reset" id="cxReset" @if(! $college && ! $topic && ! $status && $search === '') hidden @endif {!! $t('Clear all', 'مسح الكل') !!}>Clear all</a>
        </div>
    </div>

    {{-- ================================================================== the course grid --}}
    <section class="cx-main" aria-label="Courses" data-aria-en="Courses" data-aria-ar="الدورات">
        <ul class="cx-grid" id="cxGrid" @if($shown === 0) hidden @endif>
            @foreach($courses as $c)
                @php
                    $done = $c->course_status === 'completed';
                    $enrolled = $enrolledOfferingIds->contains($c->offering_id);
                    $saved = $savedOfferingIds->contains($c->offering_id);
                    $open = $isStudent && ($done || $enrolled);
                    $href = $open ? route('student.course.show', $c->offering_id) : null;
                    $desc = trim((string) $c->description);
                    $dur = $duration($c->duration_hours);
                    $sch = $schedule($c->lecture_schedule);
                    $added = $date($c->created_at);
                    $finished = $done ? $date($c->completed_at) : null;
                    $ci = $c->visible ? $vi++ : 0;
                    $cta = null;
                    if ($isStudent) {
                        $cta = $done ? ['Watch course', 'شاهد الدورة', 'play'] : ($enrolled ? ['Continue', 'واصل التعلّم', 'arrow'] : ['Enroll', 'سجّل', 'plus']);
                    }
                    $ctaIcon = $cta ? $svg($cta[2], $cta[2] === 'play' ? 'cx-keep' : '') : '';
                    $note = ! $isStudent ? null : ($done
                        ? ($saved ? ['Completed course · saved in My Courses.', 'دورة مكتملة · محفوظة في دوراتي.'] : ['Completed course · open to watch any time.', 'دورة مكتملة · متاحة للمشاهدة في أي وقت.'])
                        : ($enrolled ? ['You are enrolled · pick up where you left off.', 'أنت مسجّل · تابع من حيث توقفت.'] : ['Ongoing class · enroll to unlock the lessons.', 'دورة جارية · سجّل لفتح الدروس.']));
                    $state = $done
                        ? $svg('check').'<span '.$t('Completed', 'مكتملة').'>Completed</span>'
                        : '<i class="cx-dot" aria-hidden="true"></i><span '.$t('Ongoing', 'جارية').'>Ongoing</span>';
                @endphp
                <li class="cx-card {{ $done ? 'is-done' : 'is-live' }}" style="--ci:{{ $ci }}" @if(! $c->visible) hidden @endif
                    data-id="{{ $c->offering_id }}" data-college="{{ $c->college_code }}" data-topic="{{ $c->topic_id }}" data-status="{{ $c->course_status }}" data-search="{{ $c->search }}">

                    <div class="cx-card__media">
                        <x-course-cover :course="$c" variant="card" />
                        <span class="cx-pill cx-pill--state">{!! $state !!}</span>
                        @if($dur)<span class="cx-pill cx-pill--dur" title="{{ $dur[1] }}">{!! $svg('clock') !!}<span aria-hidden="true" {!! $t($dur[0], $dur[2]) !!}>{{ $dur[0] }}</span><span class="sr-only">{{ $dur[1] }}</span></span>@endif
                    </div>

                    <div class="cx-card__body">
                        <h3 class="cx-card__title" dir="auto">@if($href)<a href="{{ $href }}" data-title>{{ $c->title }}</a>@else<span data-title>{{ $c->title }}</span>@endif</h3>

                        <p class="cx-card__by">
                            <span class="cx-avatar" aria-hidden="true">{{ Str::upper(Str::substr($c->instructor_name, 0, 1)) }}</span>
                            <bdi class="cx-card__name">{{ $c->instructor_name }}</bdi>
                        </p>

                        <div class="cx-card__foot">
                            @if($cta)
                                @if($href)
                                    <a class="cx-cta" href="{{ $href }}"><span {!! $t($cta[0], $cta[1]) !!}>{{ $cta[0] }}</span>{!! $ctaIcon !!}</a>
                                @else
                                    <form method="post" action="{{ route('courses.enroll', $c->offering_id) }}">@csrf
                                        <button class="cx-cta" aria-label="Enroll in “{{ $c->title }}”" data-aria-en="Enroll in “{{ $c->title }}”" data-aria-ar="سجّل في «{{ $c->title }}»"><span {!! $t($cta[0], $cta[1]) !!}>{{ $cta[0] }}</span>{!! $ctaIcon !!}</button>
                                    </form>
                                @endif
                            @endif
                            <button type="button" class="cx-peek {{ $cta ? '' : 'cx-peek--wide' }}" aria-haspopup="dialog" aria-controls="cxPreview" aria-label="Course details: {{ $c->title }}" data-aria-en="Course details: {{ $c->title }}" data-aria-ar="تفاصيل الدورة: {{ $c->title }}">
                                {!! $svg('info') !!}<span {!! $t('Details', 'التفاصيل') !!}>Details</span>
                            </button>
                        </div>
                    </div>

                    {{-- the deeper information, only shown in Details --}}
                    <template class="cx-card__detail">
                        <div class="cx-dt">
                            <aside class="cx-dt__side">
                                <div class="cx-dt__media">
                                    <x-course-cover :course="$c" variant="card" />
                                    <span class="cx-pill cx-pill--state">{!! $state !!}</span>
                                </div>
                                <div class="cx-dt__intro">
                                    <h3 class="cx-dt__title" id="cxPreviewTitle" dir="auto">{{ $c->title }}</h3>
                                    <div class="cx-dt__who">
                                        <div class="cx-dt__row">
                                            <span class="cx-dt__ico" aria-hidden="true">{!! $svg('book') !!}</span>
                                            <span class="cx-dt__kv"><small {!! $t('Subject', 'المادة') !!}>Subject</small><bdi>{{ $c->course_name }}</bdi></span>
                                        </div>
                                        <div class="cx-dt__row">
                                            <span class="cx-avatar" aria-hidden="true">{{ Str::upper(Str::substr($c->instructor_name, 0, 1)) }}</span>
                                            <span class="cx-dt__kv"><small {!! $t('Instructor', 'المحاضر') !!}>Instructor</small><bdi>{{ $c->instructor_name }}</bdi></span>
                                        </div>
                                    </div>
                                </div>
                                @if($cta)
                                    <div class="cx-dt__go">
                                        @if($href)
                                            <a class="cx-cta cx-cta--big" href="{{ $href }}"><span {!! $t($cta[0], $cta[1]) !!}>{{ $cta[0] }}</span>{!! $ctaIcon !!}</a>
                                        @else
                                            <form method="post" action="{{ route('courses.enroll', $c->offering_id) }}">@csrf
                                                <button class="cx-cta cx-cta--big" aria-label="Enroll in “{{ $c->title }}”" data-aria-en="Enroll in “{{ $c->title }}”" data-aria-ar="سجّل في «{{ $c->title }}»"><span {!! $t('Enroll in this course', 'سجّل في هذه الدورة') !!}>Enroll in this course</span>{!! $ctaIcon !!}</button>
                                            </form>
                                        @endif
                                        <p class="cx-dt__note" {!! $t($note[0], $note[1]) !!}>{{ $note[0] }}</p>
                                    </div>
                                @endif
                            </aside>

                            <div class="cx-dt__main">
                                <ul class="cx-dt__stats">
                                    <li>{!! $svg('clock') !!}<b @if($dur) {!! $t($dur[0], $dur[2]) !!} @endif>{{ $dur[0] ?? '—' }}</b><span {!! $t('Total length', 'المدة الكاملة') !!}>Total length</span></li>
                                    <li>{!! $svg('calendar') !!}<b>{{ $sch && $sch['count'] ? $sch['count'] : '—' }}</b><span {!! $t('Lectures a week', 'محاضرات أسبوعياً') !!}>Lectures a week</span></li>
                                    <li>{!! $svg('users') !!}<b>{{ (int) $c->students_count }}</b><span {!! $t('Students enrolled', 'طلاب مسجّلون') !!}>Students enrolled</span></li>
                                </ul>

                                <section class="cx-dt__block">
                                    <h4><span {!! $t('About this course', 'عن هذه الدورة') !!}>About this course</span></h4>
                                    <p class="cx-dt__desc" dir="auto">@if($desc !== ''){{ $desc }}@else<span class="is-muted" {!! $t('The instructor has not added a description yet.', 'لم يضف المحاضر وصفاً بعد.') !!}>The instructor has not added a description yet.</span>@endif</p>
                                </section>

                                <section class="cx-dt__block">
                                    <h4><span {!! $t('Weekly lectures', 'المحاضرات الأسبوعية') !!}>Weekly lectures</span></h4>
                                    @if($sch && $sch['days'])
                                        <div class="cx-sched">
                                            <ol class="cx-sched__days">
                                                @foreach($week as [$day, $en, $ar])
                                                    @php $on = in_array($day, $sch['days'], true); @endphp
                                                    <li class="{{ $on ? 'is-on' : '' }}" title="{{ ucfirst($day) }}"><span aria-hidden="true" {!! $t($en, $ar) !!}>{{ $en }}</span><span class="sr-only">{{ ucfirst($day) }}{{ $on ? '' : ' (no lecture)' }}</span></li>
                                                @endforeach
                                            </ol>
                                            <div class="cx-sched__when">
                                                <p class="cx-sched__line"><span {!! $t('Every', 'كل') !!}>Every</span> <span {!! $t($sch['en'], $sch['ar']) !!}>{{ $sch['en'] }}</span></p>
                                                @if($sch['time'])<p class="cx-sched__time">{!! $svg('clock') !!}<time>{{ $sch['time'] }}</time></p>@endif
                                            </div>
                                        </div>
                                    @elseif($sch)
                                        <p class="cx-dt__desc">{{ $sch['raw'] }}</p>
                                    @else
                                        <p class="cx-dt__desc is-muted" {!! $t('No fixed lecture times — learn at your own pace.', 'لا توجد مواعيد محاضرات ثابتة — تعلّم بالسرعة التي تناسبك.') !!}>No fixed lecture times — learn at your own pace.</p>
                                    @endif
                                </section>

                                <section class="cx-dt__block">
                                    <h4><span {!! $t('Course information', 'معلومات الدورة') !!}>Course information</span></h4>
                                    <dl class="cx-facts">
                                        <div><dt {!! $t('College', 'الكلية') !!}>College</dt><dd><bdi>{{ $c->college_name }}</bdi></dd></div>
                                        <div><dt {!! $t('Topic', 'الموضوع') !!}>Topic</dt><dd>@if($c->topic_name)<bdi>{{ $c->topic_name }}</bdi>@else<span class="is-muted" {!! $t('General', 'عام') !!}>General</span>@endif</dd></div>
                                        @if($added)<div><dt {!! $t('Added', 'أُضيفت في') !!}>Added</dt><dd {!! $t($added[0], $added[1]) !!}>{{ $added[0] }}</dd></div>@endif
                                        @if($finished)<div><dt {!! $t('Completed on', 'اكتملت في') !!}>Completed on</dt><dd {!! $t($finished[0], $finished[1]) !!}>{{ $finished[0] }}</dd></div>@endif
                                    </dl>
                                </section>
                            </div>
                        </div>
                    </template>
                </li>
            @endforeach
        </ul>

        <div class="cx-empty" id="cxEmpty" @if($shown > 0) hidden @endif>
            <span class="cx-empty__art" aria-hidden="true">{!! $svg('compass') !!}</span>
            <h2 id="cxEmptyTitle">{{ $courses->isEmpty() ? 'No courses are open yet' : 'Nothing matches those filters' }}</h2>
            <p id="cxEmptyText">{{ $courses->isEmpty() ? 'Approved courses will appear here as soon as instructors publish them.' : 'Try another college or topic, or clear the search.' }}</p>
            <div class="cx-empty__actions" id="cxEmptyActions">
                @if($courses->isNotEmpty())<a href="{{ route('courses.index') }}" class="cx-cta" data-reset>Reset filters</a>@endif
            </div>
        </div>
    </section>

    {{-- ================================================================== Details (a modal; a bottom sheet on phones) --}}
    <dialog class="cx-preview" id="cxPreview" aria-labelledby="cxPreviewTitle">
        <div class="cx-preview__panel" id="cxPreviewPanel">
            <div class="cx-preview__tools">
                <button type="button" class="cx-preview__btn" data-step="-1" aria-label="Previous course" data-aria-en="Previous course" data-aria-ar="الدورة السابقة">{!! $svg('prev', 'cx-dir') !!}</button>
                <button type="button" class="cx-preview__btn" data-step="1" aria-label="Next course" data-aria-en="Next course" data-aria-ar="الدورة التالية">{!! $svg('next', 'cx-dir') !!}</button>
                <button type="button" class="cx-preview__btn" data-close aria-label="Close" data-aria-en="Close" data-aria-ar="إغلاق">{!! $svg('x') !!}</button>
            </div>
            <div class="cx-preview__frame" id="cxStageFrame"></div>
        </div>
    </dialog>

    <script type="application/json" id="cxData">@json(['topics' => (object) $topicsByCollege, 'colleges' => $colleges->pluck('college_name', 'college_code')])</script>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/zu-courses.js') }}?v={{ @filemtime(public_path('js/zu-courses.js')) }}"></script>
@endpush
