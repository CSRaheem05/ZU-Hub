@extends('layouts.glass')
@section('title', 'My Courses')
@section('header_language', '1')
@section('footer_right', 'Zarqa University · Student')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/zu-learning.css') }}?v={{ @filemtime(public_path('css/zu-learning.css')) }}">
@endpush

@section('content')
@php
    use Illuminate\Support\Str;

    // ---- one library: enrolled courses + saved completed courses ------------------------------------------------
    $view = in_array(request('view'), ['ongoing', 'completed'], true) ? request('view') : '';
    $q = mb_substr(trim((string) request('q')), 0, 80);
    $needle = mb_strtolower($q);

    $items = $courses->map(function ($c) { $c->saved_only = false; return $c; })
        ->concat($saved->map(function ($c) { $c->saved_only = true; return $c; }));
    $items->each(function ($c) use ($needle) {
        $c->search = mb_strtolower(implode(' ', [$c->title, $c->course_name, $c->college_name, $c->instructor_name]));
        $c->match = $needle === '' || str_contains($c->search, $needle);
    });
    $ongoing = $items->filter(fn ($c) => $c->course_status !== 'completed')->values();
    $done = $items->filter(fn ($c) => $c->course_status === 'completed')->values();
    $countAll = $items->where('match', true)->count();
    $countOngoing = $ongoing->where('match', true)->count();
    $countDone = $done->where('match', true)->count();
    $showOngoing = $view !== 'completed' && ! ($needle !== '' && $countOngoing === 0);
    $showDone = $view !== 'ongoing' && ! ($needle !== '' && $countDone === 0);
    $nothingShown = $items->isNotEmpty() && ! $showOngoing && ! $showDone;
    $ci = 0;   // position among visible cards (entrance stagger)

    // the avatar initial skips honorifics ("الدكتور", "د.", "Dr.") so it is a real letter, not just a stroke
    $initial = fn ($name) => Str::upper(Str::substr(trim(preg_replace('/^(الدكتورة|الدكتور|د\.|Dr\.?)\s*/u', '', (string) $name)) ?: (string) $name, 0, 1));

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
        'bookmark' => '<path d="M6 4h12v17l-6-4.2L6 21z"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'play' => '<path d="M8 5.5v13l11-6.5z"/>',
        'compass' => '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5-5 2 2-5z"/>',
        'list' => '<path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1"/><circle cx="4.5" cy="12" r="1"/><circle cx="4.5" cy="18" r="1"/>',
        'more' => '<circle cx="5" cy="12" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="19" cy="12" r="1.7"/>',
        'trash' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-12M9 7V4h6v3"/>',
        'leave' => '<path d="M9 5H5v14h4M14 8l4 4-4 4M18 12H9"/>',
    ];
    $svg = fn ($n, $c = '') => '<svg class="'.$c.'" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$n].'</svg>';
    $link = fn (array $over = []) => route('student.courses', array_filter(array_merge(['view' => $view, 'q' => $q], $over), fn ($v) => $v !== null && $v !== ''));
@endphp

<div class="mc" id="mc" data-view="{{ $view }}" data-q="{{ $q }}" data-url="{{ route('student.courses') }}">

    {{-- ============================================================ title + the one way out to Discovery --}}
    <header class="mc-head">
        <div class="mc-head__copy">
            <h1 class="mc-rv" style="--rv:0" data-en="My courses" data-ar="دوراتي">My courses</h1>
            <p class="mc-sub mc-rv" style="--rv:1" id="mcSub">
                <b id="mcSubOngoing">{{ $ongoing->count() }}</b> <span data-en="ongoing" data-ar="جارية">ongoing</span>
                <i aria-hidden="true">·</i>
                <b id="mcSubDone">{{ $done->count() }}</b> <span data-en="completed" data-ar="مكتملة">completed</span>
            </p>
        </div>
        <a href="{{ route('courses.index') }}" class="mc-find mc-rv" style="--rv:1">{!! $svg('compass') !!}<span data-en="Find more courses" data-ar="اكتشف المزيد من الدورات">Find more courses</span>{!! $svg('arrow', 'mc-find__go') !!}</a>
    </header>

    @if($items->isEmpty())

        <section class="mc-empty mc-rv" style="--rv:2">
            <span class="mc-empty__art" aria-hidden="true">{!! $svg('book') !!}</span>
            <h2 data-en="Your learning space is ready" data-ar="مساحتك التعليمية جاهزة">Your learning space is ready</h2>
            <p data-en="Enroll in an ongoing course, or save a completed one, and it will live here." data-ar="سجّل في دورة جارية، أو احفظ دورة مكتملة، وستجدها هنا.">Enroll in an ongoing course, or save a completed one, and it will live here.</p>
            <a href="{{ route('courses.index') }}" class="mc-btn">{!! $svg('compass') !!}<span data-en="Browse courses" data-ar="تصفّح الدورات">Browse courses</span></a>
        </section>

    @else

        {{-- ============================================================ floating dock: search + view --}}
        <div class="mc-dock mc-rv" style="--rv:2">
            <form class="mc-search" role="search" action="{{ route('student.courses') }}" method="get" id="mcSearchForm">
                @if($view !== '')<input type="hidden" name="view" value="{{ $view }}">@endif
                {!! $svg('search', 'mc-search__icon') !!}
                <input type="search" name="q" id="mcSearch" value="{{ $q }}" maxlength="80" autocomplete="off" placeholder="Search my courses" data-ph-en="Search my courses" data-ph-ar="ابحث في دوراتي" aria-label="Search my courses">
                <button type="button" class="mc-search__clear" id="mcSearchClear" aria-label="Clear search" @if($q === '') hidden @endif>{!! $svg('x') !!}</button>
            </form>

            <nav class="mc-seg" id="mcSeg" aria-label="Course status">
                <span class="mc-seg__thumb" aria-hidden="true"></span>
                @foreach(['' => ['All', 'الكل', $countAll], 'ongoing' => ['Ongoing', 'الجارية', $countOngoing], 'completed' => ['Completed', 'المكتملة', $countDone]] as $key => [$en, $ar, $n])
                    <a href="{{ $link(['view' => $key]) }}" data-view="{{ $key }}" class="mc-seg__item {{ $view === $key ? 'is-active' : '' }}" @if($view === $key) aria-current="true" @endif><span data-en="{{ $en }}" data-ar="{{ $ar }}">{{ $en }}</span><b data-count="{{ $key }}">{{ $n }}</b></a>
                @endforeach
            </nav>
        </div>

        {{-- ============================================================ ongoing: wide "continue" rows --}}
        <section class="mc-section mc-rv" style="--rv:3" id="mcOngoing" @if(! $showOngoing) hidden @endif aria-labelledby="mcOngoingTitle">
            <div class="mc-section__head">
                <h2 id="mcOngoingTitle" data-en="Continue learning" data-ar="واصل التعلّم">Continue learning</h2>
                <span class="mc-section__count" id="mcOngoingCount">{{ $countOngoing }}</span>
            </div>

            <div class="mc-rows" id="mcOngoingList">
                @foreach($ongoing as $c)
                    @php
                        $dur = $duration($c->duration_hours);
                        $desc = trim((string) $c->description);
                        $started = $c->lessons_started > 0;
                        $pct = $c->lessons_total > 0 ? (int) round($c->lessons_done / $c->lessons_total * 100) : 0;
                        $idx = $c->match ? $ci++ : 0;
                    @endphp
                    <article class="mc-row mc-rv mc-rv--card" style="--ci:{{ $idx }}" data-kind="ongoing" data-search="{{ $c->search }}" @if(! $c->match) hidden @endif>
                        <a class="mc-row__media" href="{{ route('student.course.show', $c->offering_id) }}" tabindex="-1" aria-hidden="true">
                            <x-course-cover :course="$c" variant="card" />
                            <span class="mc-badge mc-badge--status"><i aria-hidden="true"></i><span data-en="Ongoing" data-ar="جارية">Ongoing</span></span>
                            @if($dur)<span class="mc-badge mc-badge--dur" title="Course length: {{ $dur[1] }}">{!! $svg('clock') !!}<span aria-hidden="true">{{ $dur[0] }}</span><span class="sr-only">{{ $dur[1] }}</span></span>@endif
                        </a>

                        <div class="mc-row__body">
                            <p class="mc-subject" title="{{ $c->course_name }}">{!! $svg('book') !!}<span dir="auto">{{ $c->course_name }}</span></p>
                            <h3 class="mc-title" dir="auto"><a href="{{ route('student.course.show', $c->offering_id) }}">{{ $c->title }}</a></h3>
                            @if($desc !== '')<p class="mc-desc" dir="auto">{{ $desc }}</p>@endif

                            <div class="mc-meta">
                                <p class="mc-by"><span class="mc-avatar" aria-hidden="true">{{ $initial($c->instructor_name) }}</span><span class="mc-by__who"><strong dir="auto" title="Instructor">{{ $c->instructor_name }}</strong><small dir="auto">{{ $c->college_name }}</small></span></p>
                                <p class="mc-lessons {{ $c->lessons_total === 0 ? 'is-empty' : '' }}">{!! $svg('list') !!}@if($c->lessons_total === 0)<span data-en="No lessons yet" data-ar="لا توجد دروس بعد">No lessons yet</span>@else<span>{{ $c->lessons_total }} <span data-en="{{ $c->lessons_total === 1 ? 'lesson' : 'lessons' }}" data-ar="{{ $c->lessons_total === 1 ? 'درس' : 'دروس' }}">{{ $c->lessons_total === 1 ? 'lesson' : 'lessons' }}</span></span>@endif</p>
                            </div>

                            @if($started)
                                <div class="mc-progress" role="group" aria-label="Your progress">
                                    <div class="mc-progress__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pct }}"><span style="width: {{ $pct }}%"></span></div>
                                    <span class="mc-progress__text">{{ $c->lessons_done }} <span data-en="of" data-ar="من">of</span> {{ $c->lessons_total }} <span data-en="lessons completed" data-ar="دروس مكتملة">lessons completed</span></span>
                                </div>
                            @endif
                        </div>

                        <div class="mc-row__act">
                            <a class="mc-btn" href="{{ route('student.course.show', $c->offering_id) }}"><span data-en="{{ $c->lessons_total === 0 ? 'Open course' : 'Continue learning' }}" data-ar="{{ $c->lessons_total === 0 ? 'افتح الدورة' : 'واصل التعلّم' }}">{{ $c->lessons_total === 0 ? 'Open course' : 'Continue learning' }}</span>{!! $svg('arrow') !!}</a>
                            @include('student.partials.course-menu', ['c' => $c, 'svg' => $svg, 'kind' => 'leave'])
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mc-none" id="mcOngoingNone" @if($countOngoing > 0) hidden @endif>
                <span data-en="No ongoing classes right now." data-ar="لا توجد دورات جارية حالياً.">No ongoing classes right now.</span>
                <a href="{{ route('courses.index', ['status' => 'ongoing']) }}" data-en="Find a class" data-ar="ابحث عن دورة">Find a class</a>
            </div>
        </section>

        {{-- ============================================================ completed (and saved): compact poster grid --}}
        <section class="mc-section mc-rv" style="--rv:4" id="mcDone" @if(! $showDone) hidden @endif aria-labelledby="mcDoneTitle">
            <div class="mc-section__head">
                <h2 id="mcDoneTitle" data-en="Completed courses" data-ar="الدورات المكتملة">Completed courses</h2>
                <span class="mc-section__count" id="mcDoneCount">{{ $countDone }}</span>
            </div>

            <div class="mc-grid" id="mcDoneList">
                @foreach($done as $c)
                    @php
                        $dur = $duration($c->duration_hours);
                        $idx = $c->match ? $ci++ : 0;
                    @endphp
                    <article class="mc-card mc-rv mc-rv--card" style="--ci:{{ $idx }}" data-kind="completed" data-search="{{ $c->search }}" @if(! $c->match) hidden @endif>
                        <a class="mc-card__media" href="{{ route('student.course.show', $c->offering_id) }}" tabindex="-1" aria-hidden="true">
                            <x-course-cover :course="$c" variant="card" />
                            <span class="mc-badge mc-badge--status">{!! $svg('check') !!}<span data-en="Completed" data-ar="مكتملة">Completed</span></span>
                            @if($dur)<span class="mc-badge mc-badge--dur" title="Course length: {{ $dur[1] }}">{!! $svg('clock') !!}<span aria-hidden="true">{{ $dur[0] }}</span><span class="sr-only">{{ $dur[1] }}</span></span>@endif
                        </a>

                        <div class="mc-card__body">
                            <p class="mc-subject" title="{{ $c->course_name }}">{!! $svg('book') !!}<span dir="auto">{{ $c->course_name }}</span></p>
                            <h3 class="mc-title" dir="auto"><a href="{{ route('student.course.show', $c->offering_id) }}">{{ $c->title }}</a></h3>

                            <div class="mc-card__foot">
                                <p class="mc-by"><span class="mc-avatar" aria-hidden="true">{{ $initial($c->instructor_name) }}</span><span class="mc-by__who"><strong dir="auto" title="Instructor">{{ $c->instructor_name }}</strong><small>@if($c->saved_only){!! $svg('bookmark') !!}<span data-en="Saved" data-ar="محفوظة">Saved</span>@else<bdi>{{ $c->college_name }}</bdi>@endif</small></span></p>
                                <div class="mc-card__act">
                                    <a class="mc-btn" href="{{ route('student.course.show', $c->offering_id) }}"><span data-en="Watch course" data-ar="شاهد الدورة">Watch course</span>{!! $svg('play') !!}</a>
                                    @include('student.partials.course-menu', ['c' => $c, 'svg' => $svg, 'kind' => $c->saved_only ? 'unsave' : 'leave'])
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mc-none" id="mcDoneNone" @if($countDone > 0) hidden @endif>
                <span data-en="Completed courses you watch or save will appear here." data-ar="الدورات المكتملة التي تشاهدها أو تحفظها ستظهر هنا.">Completed courses you watch or save will appear here.</span>
                <a href="{{ route('courses.index', ['status' => 'completed']) }}" data-en="Browse completed courses" data-ar="تصفّح الدورات المكتملة">Browse completed courses</a>
            </div>
        </section>

        {{-- nothing matches the search --}}
        <section class="mc-empty" id="mcEmpty" @if(! $nothingShown) hidden @endif>
            <span class="mc-empty__art" aria-hidden="true">{!! $svg('search') !!}</span>
            <h2 data-en="No courses match your search" data-ar="لا توجد دورات مطابقة لبحثك">No courses match your search</h2>
            <p data-en="Try a different word, or clear the search to see everything." data-ar="جرّب كلمة أخرى، أو امسح البحث لعرض كل شيء.">Try a different word, or clear the search to see everything.</p>
            <a href="{{ route('student.courses') }}" class="mc-btn" id="mcClear"><span data-en="Clear search" data-ar="مسح البحث">Clear search</span></a>
        </section>

    @endif
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/zu-my-courses.js') }}?v={{ @filemtime(public_path('js/zu-my-courses.js')) }}"></script>
<script src="{{ asset('js/zu-learning.js') }}?v={{ @filemtime(public_path('js/zu-learning.js')) }}"></script>
@endpush
