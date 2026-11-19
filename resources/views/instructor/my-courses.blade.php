@extends('layouts.glass')
@section('title', 'My Courses')
@section('footer_right', 'Zarqa University · Instructor workspace')
@section('header_language', '1')

@push('styles')
{{-- apply the saved language before the first paint (shared `zu-language` preference with the course catalog) --}}
<script>try{if(localStorage.getItem('zu-language')==='ar'){var h=document.documentElement;h.lang='ar';h.dir='rtl';h.classList.add('cx-ar');}}catch(e){}</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap">
{{-- the catalog's light card system (tokens, cards, glass badges, toolbar, Details dialog) + the instructor layer on top --}}
<link rel="stylesheet" href="{{ asset('css/zu-courses.css') }}?v={{ @filemtime(public_path('css/zu-courses.css')) }}">
<link rel="stylesheet" href="{{ asset('css/zu-instructor-courses.css') }}?v={{ @filemtime(public_path('css/zu-instructor-courses.css')) }}">
@endpush

@section('content')
@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    $me = auth()->user();
    $initial = Str::upper(Str::substr($me->name, 0, 1));

    // bilingual text: English is rendered, the page script swaps to Arabic (same data-en / data-ar convention as /courses)
    $t = fn (string $en, string $ar) => 'data-en="'.e($en).'" data-ar="'.e($ar).'"';
    $arEnrolled = fn (int $n) => $n === 1 ? 'طالب مسجّل' : ($n === 2 ? 'طالبان مسجّلان' : ($n >= 11 ? 'طالباً مسجّلاً' : 'طلاب مسجّلون'));

    // stored decimal hours -> ["5h 30m", "5 hours 30 minutes", "5 س 30 د"]
    $duration = function ($h) {
        $m = (int) round(((float) $h) * 60);
        if ($m <= 0) return null;
        [$hh, $mm] = [intdiv($m, 60), $m % 60];
        $join = fn ($a, $b, $sep = ' ') => trim($a.($a !== '' && $b !== '' ? $sep : '').$b);
        return [
            $join($hh ? $hh.'h' : '', $mm ? $mm.'m' : ''),
            $join($hh ? $hh.' '.Str::plural('hour', $hh) : '', $mm ? $mm.' '.Str::plural('minute', $mm) : ''),
            $join($hh ? $hh.' س' : '', $mm ? $mm.' د' : ''),
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
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.01"/>',
        'calendar' => '<rect x="4" y="5" width="16" height="15" rx="3"/><path d="M4 10h16M9 3v4M15 3v4"/>',
        'book' => '<path d="M4 5.5A1.5 1.5 0 0 1 5.5 4H20v14H5.5A1.5 1.5 0 0 0 4 19.5z"/><path d="M4 19.5A1.5 1.5 0 0 0 5.5 21H20"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.4-3.6 2.8-5.5 6.5-5.5s6.1 1.9 6.5 5.5"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8M18.5 14.8c1.8.7 2.8 2.4 3 5.2"/>',
        'lessons' => '<rect x="3" y="4.5" width="18" height="13" rx="3"/><path d="m10.5 8.8 4 2.4-4 2.4zM8 21h8"/>',
        'edit' => '<path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>',
        'dots' => '<circle cx="5" cy="12" r="1.3"/><circle cx="12" cy="12" r="1.3"/><circle cx="19" cy="12" r="1.3"/>',
        'inbox' => '<path d="M8 3h8l4 4v13a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M16 3v4h4M10 12h6M10 16h4"/>',
        'redo' => '<path d="M4 12a8 8 0 0 1 14-5.3L20 9M20 4v5h-5"/>',
        'done' => '<circle cx="12" cy="12" r="9"/><path d="m8 12.5 3 3 5-6"/>',
        'trash' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-12M9 7V4h6v3"/>',
        'compass' => '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5-5 2 2-5z"/>',
        'building' => '<path d="M4 21V6l8-3 8 3v15M9 21v-5h6v5M8 9h.01M12 9h.01M16 9h.01M8 12.5h.01M12 12.5h.01M16 12.5h.01"/>',
        'prev' => '<path d="m15 6-6 6 6 6"/>',
        'next' => '<path d="m9 6 6 6-6 6"/>',
    ];
    $svg = fn ($n, $c = '') => '<svg class="'.$c.'" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$n].'</svg>';

    $total = $courses->count();
    $ongoing = $courses->where('course_status', 'ongoing')->count();
    $enrollments = (int) $courses->sum('students_count');
@endphp

<div class="cx iwx" id="iwx">

    {{-- ================================================================== workspace header (compact) --}}
    <header class="iwx-head cx-in" style="--in:0">
        <div class="iwx-head__top">
            <div class="iwx-head__id">
                <span class="iwx-head__mark" aria-hidden="true">{{ $initial }}</span>
                <div class="iwx-head__text">
                    <p class="iwx-kicker"><span {!! $t('Instructor workspace', 'مساحة المحاضر') !!}>Instructor workspace</span><i aria-hidden="true"></i><bdi>{{ $me->name }}</bdi></p>
                    <h1 {!! $t('My Courses', 'دوراتي') !!}>My Courses</h1>
                </div>
            </div>

            <div class="iwx-head__actions">
                <a href="{{ route('instructor.requests') }}" class="iwx-req">
                    {!! $svg('inbox', 'iwx-req__icon') !!}
                    <span {!! $t('Course requests', 'طلبات الدورات') !!}>Course requests</span>
                    @if($needsChanges > 0)
                        <b class="iwx-flag iwx-flag--warn" {!! $t($needsChanges.' '.($needsChanges === 1 ? 'needs' : 'need').' changes', $needsChanges.' بحاجة لتعديل') !!}>{{ $needsChanges }} {{ $needsChanges === 1 ? 'needs' : 'need' }} changes</b>
                    @endif
                    @if($pendingRequests > 0)
                        <b class="iwx-flag" {!! $t($pendingRequests.' pending', $pendingRequests.' قيد المراجعة') !!}>{{ $pendingRequests }} pending</b>
                    @endif
                </a>
                <a href="{{ route('instructor.courses.create') }}" class="cx-cta iwx-create">
                    {!! $svg('plus', 'cx-keep') !!}<span {!! $t('Create course', 'إنشاء دورة') !!}>Create course</span>
                </a>
            </div>
        </div>

        @if($total > 0)
            <dl class="iwx-stats">
                <div><dt {!! $t('Courses', 'الدورات') !!}>Courses</dt><dd data-tick="{{ $total }}">{{ $total }}</dd></div>
                <div class="is-live"><dt {!! $t('Ongoing', 'جارية') !!}>Ongoing</dt><dd data-tick="{{ $ongoing }}">{{ $ongoing }}</dd></div>
                <div><dt {!! $t('Completed', 'مكتملة') !!}>Completed</dt><dd data-tick="{{ $total - $ongoing }}">{{ $total - $ongoing }}</dd></div>
                <div><dt {!! $t('Enrollments', 'التسجيلات') !!}>Enrollments</dt><dd data-tick="{{ $enrollments }}">{{ $enrollments }}</dd></div>
            </dl>
        @endif
    </header>

@if($courses->isEmpty())

    {{-- ================================================================== no approved course yet --}}
    <section class="cx-empty iwx-first cx-in" style="--in:2">
        <span class="cx-empty__art" aria-hidden="true">{!! $svg('lessons') !!}</span>
        <h2 {!! $t('No courses yet', 'لا توجد دورات بعد') !!}>No courses yet</h2>
        <p {!! $t('Create your first course and send it for approval. Once it is approved it appears here and students can find it.', 'أنشئ دورتك الأولى وأرسلها للاعتماد. بعد اعتمادها تظهر هنا ويستطيع الطلاب العثور عليها.') !!}>Create your first course and send it for approval. Once it is approved it appears here and students can find it.</p>
        <div class="cx-empty__actions">
            <a href="{{ route('instructor.courses.create') }}" class="cx-cta">{!! $svg('plus', 'cx-keep') !!}<span {!! $t('Create your first course', 'أنشئ دورتك الأولى') !!}>Create your first course</span></a>
            <a href="{{ route('instructor.requests') }}" class="cx-ghost" {!! $t('View course requests', 'عرض طلبات الدورات') !!}>View course requests</a>
        </div>
    </section>

@else

    {{-- ================================================================== search + status (one calm toolbar) --}}
    <section class="cx-bar iwx-bar cx-in" style="--in:2" aria-label="Find your courses" data-aria-en="Find your courses" data-aria-ar="ابحث في دوراتك">
        <div class="cx-search" role="search">
            <label for="iwxSearch" class="sr-only" {!! $t('Search your courses', 'ابحث في دوراتك') !!}>Search your courses</label>
            {!! $svg('search', 'cx-search__icon') !!}
            <input type="search" id="iwxSearch" maxlength="80" autocomplete="off" placeholder="Search by title, subject or description" data-ph-en="Search by title, subject or description" data-ph-ar="ابحث بالعنوان أو المادة أو الوصف">
            <kbd class="cx-search__key" aria-hidden="true">/</kbd>
            <button type="button" class="cx-search__clear" id="iwxSearchClear" aria-label="Clear search" data-aria-en="Clear search" data-aria-ar="مسح البحث" hidden>{!! $svg('x') !!}</button>
        </div>

        <div class="cx-seg" id="iwxSeg" role="group" aria-label="Filter by course status" data-aria-en="Filter by course status" data-aria-ar="التصفية حسب حالة الدورة">
            @foreach(['' => ['All', 'الكل', $total], 'ongoing' => ['Ongoing', 'جارية', $ongoing], 'completed' => ['Completed', 'مكتملة', $total - $ongoing]] as $key => [$en, $ar, $n])
                <button type="button" data-status="{{ $key }}" class="cx-seg__item {{ $key === '' ? 'is-active' : '' }}" aria-pressed="{{ $key === '' ? 'true' : 'false' }}">
                    @if($key === 'ongoing')<i class="cx-dot" aria-hidden="true"></i>@elseif($key === 'completed'){!! $svg('check') !!}@endif<span {!! $t($en, $ar) !!}>{{ $en }}</span><b data-count="{{ $key }}">{{ $n }}</b>
                </button>
            @endforeach
        </div>
    </section>

    <div class="cx-result iwx-result cx-in" style="--in:3">
        <p class="cx-result__count" role="status" aria-live="polite"><b id="iwxCount">{{ $total }}</b> <span id="iwxCountLabel">{{ Str::plural('course', $total) }}</span></p>
        <button type="button" class="cx-reset" id="iwxReset" hidden {!! $t('Clear filters', 'مسح التصفية') !!}>Clear filters</button>
    </div>

    {{-- ================================================================== the course grid --}}
    <section class="cx-main" aria-label="Your courses" data-aria-en="Your courses" data-aria-ar="دوراتك">
        <ul class="cx-grid iwx-grid" id="iwxGrid">
            @foreach($courses as $c)
                @php
                    $done = $c->course_status === 'completed';
                    $desc = trim((string) $c->description);
                    $dur = $duration($c->duration_hours);
                    $sch = $schedule($c->lecture_schedule);
                    $added = $date($c->created_at);
                    $finished = $done ? $date($c->completed_at ?? null) : null;
                    $n = (int) $c->students_count;
                    $lessons = route('instructor.lessons.index', $c->offering_id);
                    $studentsUrl = route('instructor.students', $c->offering_id);
                    $editUrl = route('instructor.courses.edit', $c->offering_id);
                    $haystack = mb_strtolower(implode(' ', [$c->title, $c->course_name, $desc, $c->topic_name, $c->college_name]));
                    $state = $done
                        ? $svg('check').'<span '.$t('Completed', 'مكتملة').'>Completed</span>'
                        : '<i class="cx-dot" aria-hidden="true"></i><span '.$t('Ongoing', 'جارية').'>Ongoing</span>';
                    $studentsLine = '<b>'.$n.'</b> <span '.$t(Str::plural('student', $n).' enrolled', $arEnrolled($n)).'>'.Str::plural('student', $n).' enrolled</span>';
                @endphp
                <li class="cx-card iwx-card {{ $done ? 'is-done' : 'is-live' }}" style="--ci:{{ $loop->index }}"
                    data-id="{{ $c->offering_id }}" data-status="{{ $c->course_status }}" data-search="{{ $haystack }}">

                    <a class="cx-card__media iwx-card__media" href="{{ $lessons }}" tabindex="-1" aria-hidden="true">
                        <x-course-cover :course="$c" variant="card" alt="" />
                        <span class="cx-pill cx-pill--state">{!! $state !!}</span>
                        @if($dur)<span class="cx-pill cx-pill--dur" title="{{ $dur[1] }}">{!! $svg('clock') !!}<span {!! $t($dur[0], $dur[2]) !!}>{{ $dur[0] }}</span></span>@endif
                    </a>

                    <div class="cx-card__body iwx-card__body">
                        <p class="iwx-card__subject">{!! $svg('book') !!}<bdi>{{ $c->course_name }}</bdi></p>
                        <h3 class="cx-card__title" dir="auto"><a href="{{ $lessons }}" data-title>{{ $c->title }}</a></h3>
                        <p class="iwx-card__meta">{!! $svg('users') !!}<span>{!! $studentsLine !!}</span></p>

                        <div class="iwx-card__foot">
                            <a class="cx-cta iwx-primary" href="{{ $lessons }}">
                                {!! $svg('lessons', 'cx-keep') !!}<span {!! $t('Manage lessons', 'إدارة الدروس') !!}>Manage lessons</span>{!! $svg('arrow', 'iwx-primary__go') !!}
                            </a>

                            <div class="iwx-tools" role="group" aria-label="More for {{ $c->title }}" data-aria-en="More for {{ $c->title }}" data-aria-ar="المزيد لـ {{ $c->title }}">
                                <a class="iwx-tool" href="{{ $studentsUrl }}">{!! $svg('users') !!}<span {!! $t('Students', 'الطلاب') !!}>Students</span></a>
                                <a class="iwx-tool" href="{{ $editUrl }}">{!! $svg('edit') !!}<span {!! $t('Edit', 'تعديل') !!}>Edit</span></a>
                                <button type="button" class="iwx-tool iwx-peek" aria-haspopup="dialog" aria-controls="iwxPreview" aria-label="Course details: {{ $c->title }}" data-aria-en="Course details: {{ $c->title }}" data-aria-ar="تفاصيل الدورة: {{ $c->title }}">{!! $svg('info') !!}<span {!! $t('Details', 'التفاصيل') !!}>Details</span></button>

                                <details class="iwx-more">
                                    <summary class="iwx-tool iwx-tool--icon" aria-label="More actions for {{ $c->title }}" data-aria-en="More actions for {{ $c->title }}" data-aria-ar="إجراءات أخرى لـ {{ $c->title }}">{!! $svg('dots') !!}</summary>
                                    <div class="iwx-more__menu" role="menu">
                                        <form method="POST" action="{{ route('instructor.courses.complete', $c->offering_id) }}" data-submit-once>@csrf @method('PATCH')
                                            @if($done)
                                                <button type="submit" class="iwx-more__item" role="menuitem">{!! $svg('redo') !!}<span {!! $t('Mark as ongoing', 'إعادتها كجارية') !!}>Mark as ongoing</span></button>
                                            @else
                                                <button type="submit" class="iwx-more__item iwx-more__item--green" role="menuitem">{!! $svg('done') !!}<span {!! $t('Mark as completed', 'تحديدها كمكتملة') !!}>Mark as completed</span></button>
                                            @endif
                                        </form>
                                        <hr>
                                        <form method="POST" action="{{ route('instructor.courses.destroy', $c->offering_id) }}">@csrf @method('DELETE')
                                            <button type="submit" class="iwx-more__item iwx-more__item--red" role="menuitem" data-zu-confirm
                                                    data-confirm-tone="red" data-confirm-icon="warning"
                                                    data-confirm-title="Delete this course?"
                                                    data-confirm-message="“{{ $c->title }}” will be permanently deleted. This cannot be undone."
                                                    data-confirm-label="Yes, delete"
                                                    data-confirm-ar="حذف هذه الدورة؟|ستُحذف «{{ $c->title }}» نهائياً ولا يمكن التراجع عن ذلك.|نعم، احذفها">
                                                {!! $svg('trash') !!}<span {!! $t('Delete course', 'حذف الدورة') !!}>Delete course</span>
                                            </button>
                                        </form>
                                    </div>
                                </details>
                            </div>
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
                                    <h3 class="cx-dt__title" id="iwxPreviewTitle" dir="auto">{{ $c->title }}</h3>
                                    <div class="cx-dt__who">
                                        <div class="cx-dt__row">
                                            <span class="cx-dt__ico" aria-hidden="true">{!! $svg('book') !!}</span>
                                            <span class="cx-dt__kv"><small {!! $t('Subject', 'المادة') !!}>Subject</small><bdi>{{ $c->course_name }}</bdi></span>
                                        </div>
                                        <div class="cx-dt__row">
                                            <span class="cx-avatar" aria-hidden="true">{{ $initial }}</span>
                                            <span class="cx-dt__kv"><small {!! $t('Instructor', 'المحاضر') !!}>Instructor</small><bdi>{{ $me->name }}</bdi></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="cx-dt__go iwx-dt__go">
                                    <a class="cx-cta cx-cta--big" href="{{ $lessons }}">{!! $svg('lessons', 'cx-keep') !!}<span {!! $t('Manage lessons', 'إدارة الدروس') !!}>Manage lessons</span></a>
                                    <div class="iwx-dt__more">
                                        <a class="cx-ghost" href="{{ $studentsUrl }}">{!! $svg('users') !!}<span {!! $t('View students', 'عرض الطلاب') !!}>View students</span></a>
                                        <a class="cx-ghost" href="{{ $editUrl }}">{!! $svg('edit') !!}<span {!! $t('Edit course', 'تعديل الدورة') !!}>Edit course</span></a>
                                    </div>
                                </div>
                            </aside>

                            <div class="cx-dt__main">
                                <ul class="cx-dt__stats">
                                    <li>{!! $svg('clock') !!}<b @if($dur) {!! $t($dur[0], $dur[2]) !!} @endif>{{ $dur[0] ?? '—' }}</b><span {!! $t('Total length', 'المدة الكاملة') !!}>Total length</span></li>
                                    <li>{!! $svg('calendar') !!}<b>{{ $sch && $sch['count'] ? $sch['count'] : '—' }}</b><span {!! $t('Lectures a week', 'محاضرات أسبوعياً') !!}>Lectures a week</span></li>
                                    <li>{!! $svg('users') !!}<b>{{ $n }}</b><span {!! $t('Students enrolled', 'طلاب مسجّلون') !!}>Students enrolled</span></li>
                                </ul>

                                <section class="cx-dt__block">
                                    <h4><span {!! $t('About this course', 'عن هذه الدورة') !!}>About this course</span></h4>
                                    <p class="cx-dt__desc" dir="auto">@if($desc !== ''){{ $desc }}@else<span class="is-muted" {!! $t('No description yet. Students see a default line until you add one with Edit course.', 'لا يوجد وصف بعد. يرى الطلاب نصاً افتراضياً حتى تضيف وصفاً من تعديل الدورة.') !!}>No description yet. Students see a default line until you add one with Edit course.</span>@endif</p>
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
                                        <p class="cx-dt__desc is-muted" {!! $t('No fixed lecture times set for this course.', 'لم تُحدَّد مواعيد محاضرات ثابتة لهذه الدورة.') !!}>No fixed lecture times set for this course.</p>
                                    @endif
                                </section>

                                <section class="cx-dt__block">
                                    <h4><span {!! $t('Course information', 'معلومات الدورة') !!}>Course information</span></h4>
                                    <dl class="cx-facts">
                                        <div><dt {!! $t('College', 'الكلية') !!}>College</dt><dd>@if($c->college_name)<bdi>{{ $c->college_name }}</bdi>@else<span class="is-muted">—</span>@endif</dd></div>
                                        <div><dt {!! $t('Topic', 'الموضوع') !!}>Topic</dt><dd>@if($c->topic_name)<bdi>{{ $c->topic_name }}</bdi>@else<span class="is-muted" {!! $t('General', 'عام') !!}>General</span>@endif</dd></div>
                                        <div><dt {!! $t('Status', 'الحالة') !!}>Status</dt><dd {!! $done ? $t('Completed', 'مكتملة') : $t('Ongoing', 'جارية') !!}>{{ $done ? 'Completed' : 'Ongoing' }}</dd></div>
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

        <div class="cx-empty" id="iwxEmpty" hidden>
            <span class="cx-empty__art" aria-hidden="true">{!! $svg('compass') !!}</span>
            <h2 {!! $t('No matching courses', 'لا توجد دورات مطابقة') !!}>No matching courses</h2>
            <p {!! $t('Try a different search, or switch the filter back to “All”.', 'جرّب بحثاً آخر، أو أعد التصفية إلى «الكل».') !!}>Try a different search, or switch the filter back to “All”.</p>
            <div class="cx-empty__actions"><button type="button" class="cx-cta" data-reset {!! $t('Show all courses', 'اعرض كل الدورات') !!}>Show all courses</button></div>
        </div>
    </section>

    {{-- ================================================================== Details (a modal; a bottom sheet on phones) --}}
    <dialog class="cx-preview" id="iwxPreview" aria-labelledby="iwxPreviewTitle">
        <div class="cx-preview__panel" id="iwxPreviewPanel">
            <div class="cx-preview__tools">
                <button type="button" class="cx-preview__btn" data-step="-1" aria-label="Previous course" data-aria-en="Previous course" data-aria-ar="الدورة السابقة">{!! $svg('prev', 'cx-dir') !!}</button>
                <button type="button" class="cx-preview__btn" data-step="1" aria-label="Next course" data-aria-en="Next course" data-aria-ar="الدورة التالية">{!! $svg('next', 'cx-dir') !!}</button>
                <button type="button" class="cx-preview__btn" data-close aria-label="Close" data-aria-en="Close" data-aria-ar="إغلاق">{!! $svg('x') !!}</button>
            </div>
            <div class="cx-preview__frame" id="iwxStageFrame"></div>
        </div>
    </dialog>

@endif
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/zu-instructor-courses.js') }}?v={{ @filemtime(public_path('js/zu-instructor-courses.js')) }}"></script>
@endpush
