@extends('layouts.glass')

@php
    use App\Services\LessonFileService;
    use App\Support\LessonIcons as I;
    use App\Support\VideoEmbed;
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    $preview = $preview ?? false;
    $files = app(LessonFileService::class);

    // students only see sections that have something in them
    $visible = $sections->filter(fn ($s) => $s->lessons->isNotEmpty())->values();
    $total = $visible->sum(fn ($s) => $s->lessons->count());
    $key = (string) $course->offering_id;
    $uid = 0;
@endphp

@section('title', $course->title)
@section('footer_right', 'Zarqa University')

@push('styles')
<noscript><style>.cl-section.is-collapsed .cl-section__panel { grid-template-rows: 1fr !important; visibility: visible !important; } .cl-section.is-collapsed .cl-section__inner { opacity: 1 !important; transform: none !important; } .cl-bar__tools { display: none !important; }</style></noscript>
<link rel="stylesheet" href="{{ asset('css/zu-course-learn.css') }}?v={{ @filemtime(public_path('css/zu-course-learn.css')) }}">
<link rel="stylesheet" href="{{ asset('css/zu-lightbox.css') }}?v={{ @filemtime(public_path('css/zu-lightbox.css')) }}">
@endpush

@section('content')
<div class="cl-page" id="clRoot" data-course="{{ $key }}">

    @if($preview)
        <div class="cl-preview reveal" style="--i:0" role="status">
            <span class="cl-preview__tag">Student preview</span>
            <span class="cl-preview__text">This is exactly how students see your course.</span>
            <a href="{{ route('instructor.lessons.index', $course->offering_id) }}" class="cl-preview__back">{!! I::svg('back') !!}Back to course content</a>
        </div>
    @else
        <a href="{{ route('student.courses') }}" class="cl-back reveal" style="--i:0">{!! I::svg('back') !!}My courses</a>
    @endif

    {{-- ============================================================ course heading --}}
    <header class="cl-head reveal" style="--i:1">
        <x-course-cover :course="$course" variant="wide" class="cl-cover" />
        <div class="cl-head__text">
            <span class="cl-chip cl-chip--subject">{{ $course->course_name }}</span>
            <h1>{{ $course->title }}</h1>
            @if($course->description)<p class="cl-head__desc">{{ $course->description }}</p>@endif
            <ul class="cl-facts">
                <li>{!! I::svg('user') !!}<span>{{ $course->instructor_name }}</span></li>
                @if($course->lecture_schedule)<li>{!! I::svg('calendar') !!}<span>{{ $course->lecture_schedule }}</span></li>@endif
                <li class="cl-fact--status cl-fact--{{ $course->course_status }}"><i aria-hidden="true"></i><span>{{ $course->course_status === 'completed' ? 'Completed' : 'Ongoing' }}</span></li>
            </ul>
        </div>
    </header>

    @if($visible->isEmpty())

        <section class="cl-empty reveal" style="--i:2">
            <div class="cl-empty__icon" aria-hidden="true">{!! I::svg('layers') !!}</div>
            <h2>No content yet</h2>
            <p>{{ $preview ? 'Add sections and content on the course content page. Students will see them here.' : 'Your instructor has not added any content to this course yet. Please check back later.' }}</p>
        </section>

    @else

        <div class="cl-bar reveal" style="--i:2">
            <p>{{ $visible->count() }} {{ Str::plural('section', $visible->count()) }} <span aria-hidden="true">·</span> {{ $total }} {{ Str::plural('item', $total) }}</p>
            @if($visible->count() > 1)
                <div class="cl-bar__tools" role="group" aria-label="Sections">
                    <button type="button" class="cl-tool" data-cl-all="open" title="Open every section"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 9 5-5 5 5"/><path d="m7 15 5 5 5-5"/></svg>Expand all</button>
                    <button type="button" class="cl-tool" data-cl-all="close" title="Close every section"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 5 5 5 5-5"/><path d="m7 19 5-5 5 5"/></svg>Collapse all</button>
                </div>
            @endif
        </div>

        <div class="cl-sections">
            @foreach($visible as $section)
                @php $sid = $section->section_id ?: 'default'; $n = $section->lessons->count(); @endphp
                <section class="cl-section is-collapsed reveal" style="--i:{{ min($loop->index + 3, 8) }}" data-section-id="{{ $sid }}">
                    <h2 class="cl-section__head">
                        <button type="button" class="cl-section__toggle" aria-expanded="false" aria-controls="cs-{{ $sid }}">
                            {!! I::svg('chevron', 'cl-chev') !!}
                            <span class="cl-section__title">{{ $section->title }}</span>
                            <span class="cl-section__count">{{ $n }} {{ Str::plural('item', $n) }}</span>
                        </button>
                    </h2>

                    <div class="cl-section__panel">
                    <div class="cl-section__body" id="cs-{{ $sid }}" role="region" aria-label="{{ $section->title }}">
                    <div class="cl-section__inner">
                        @foreach($section->lessons as $lesson)
                            @php
                                $type = $lesson->lesson_type;
                                $isUpload = $type === 'video' && ! empty($lesson->file_path);
                                $ext = ($type === 'file' || $isUpload) ? strtolower(pathinfo((string) $lesson->file_name, PATHINFO_EXTENSION)) : null;
                                $kind = $type === 'file' ? 'file-'.($ext ?: 'file') : $type;
                                $label = match (true) {
                                    $type === 'file' => LessonFileService::DOCUMENT_TYPES[$ext][0] ?? 'Material',
                                    $type === 'video' => 'Video',
                                    $type === 'link' => 'Resource',
                                    default => 'Live session',
                                };
                                $desc = trim((string) ($lesson->description ?? ''));
                                $fileUrl = route('lessons.file', ['lessonId' => $lesson->lesson_id]);
                                $when = $type === 'live' && $lesson->scheduled_at ? Carbon::parse($lesson->scheduled_at) : null;
                            @endphp

                            <article class="cl-item cl-k--{{ $kind }}" id="lesson-{{ $lesson->lesson_id }}">
                                <div class="cl-item__head">
                                    <span class="cl-tile" aria-hidden="true">@if($type === 'file' && $ext)<b>{{ strtoupper($ext) }}</b>@else{!! I::svg($type) !!}@endif</span>
                                    <div class="cl-item__titles">
                                        <h3>{{ $lesson->lesson_title }}</h3>
                                        <span class="cl-chip">@if($type === 'live')<i aria-hidden="true"></i>@endif{{ $label }}</span>
                                    </div>
                                </div>

                                @if($desc !== '')<p class="cl-item__desc">{{ $desc }}</p>@endif

                                {{-- ---------------------------------------------------- video --}}
                                @if($type === 'video')
                                    @php $embed = VideoEmbed::resolve($lesson->lesson_url); @endphp

                                    @if($isUpload)
                                        <div class="cl-player">
                                            <video controls preload="metadata" playsinline controlsList="nodownload" aria-label="{{ $lesson->lesson_title }}">
                                                <source src="{{ $fileUrl }}?view=1" type="video/mp4">
                                                Your browser cannot play this video.
                                            </video>
                                        </div>
                                    @elseif($embed['embed'])
                                        <div class="cl-player">
                                            <iframe src="{{ $embed['embed'] }}" title="{{ $lesson->lesson_title }}" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"
                                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                                        </div>
                                    @elseif($embed['direct'])
                                        <div class="cl-player">
                                            <video controls preload="metadata" playsinline aria-label="{{ $lesson->lesson_title }}"><source src="{{ $lesson->lesson_url }}" type="video/mp4">Your browser cannot play this video.</video>
                                        </div>
                                    @elseif($lesson->lesson_url)
                                        <a class="cl-btn" href="{{ $lesson->lesson_url }}" target="_blank" rel="noopener noreferrer">{!! I::svg('play') !!}Open video</a>
                                    @else
                                        <p class="cl-note">This video is not available yet.</p>
                                    @endif

                                {{-- ------------------------------------------------ material --}}
                                @elseif($type === 'file')
                                    @if($lesson->file_path)
                                        <div class="cl-file">
                                            @if(in_array($lesson->file_mime, ['image/jpeg', 'image/png', 'image/webp'], true))
                                                <button type="button" class="zu-thumb" data-zu-lightbox="{{ $fileUrl }}?view=1" data-caption="{{ $lesson->lesson_title }}" aria-label="View image “{{ $lesson->lesson_title }}” full size"><img src="{{ $fileUrl }}?view=1" alt="" width="56" height="56" loading="lazy" decoding="async"></button>
                                            @endif
                                            <span class="cl-file__info">
                                                <strong>{{ $lesson->file_name }}</strong>
                                                @if($lesson->file_size)<small>{{ $files->humanSize((int) $lesson->file_size) }}</small>@endif
                                            </span>
                                            <span class="cl-file__actions">
                                                @if($lesson->file_mime === 'application/pdf')
                                                    <a class="cl-btn cl-btn--ghost" href="{{ $fileUrl }}?view=1" target="_blank" rel="noopener">{!! I::svg('eye') !!}View</a>
                                                @endif
                                                <a class="cl-btn" href="{{ $fileUrl }}">{!! I::svg('download') !!}Download</a>
                                            </span>
                                        </div>
                                    @else
                                        <p class="cl-note">This material is not available yet.</p>
                                    @endif

                                {{-- ----------------------------------------------- resource --}}
                                @elseif($type === 'link')
                                    @if($lesson->lesson_url)
                                        <a class="cl-btn" href="{{ $lesson->lesson_url }}" target="_blank" rel="noopener noreferrer">{!! I::svg('globe') !!}Open resource<small>{{ preg_replace('/^www\./', '', (string) parse_url($lesson->lesson_url, PHP_URL_HOST)) }}</small></a>
                                    @else
                                        <p class="cl-note">This resource is not available yet.</p>
                                    @endif

                                {{-- ------------------------------------ Microsoft Teams session --}}
                                @elseif($type === 'live')
                                    <div class="cl-live">
                                        <div class="cl-live__when">
                                            @if($when)
                                                {!! I::svg('calendar') !!}
                                                <span><strong>{{ $when->format('l, F j') }}</strong><small>{{ $when->format('g:i A') }}@if($when->isFuture()) · Upcoming @else · Session time has passed @endif</small></span>
                                            @else
                                                {!! I::svg('clock') !!}<span><strong>Time to be announced</strong></span>
                                            @endif
                                        </div>
                                        @if($lesson->live_url)
                                            @php $isTeams = \App\Support\TeamsUrl::isValid($lesson->live_url); @endphp
                                            <a class="cl-btn {{ $isTeams ? 'cl-btn--teams' : '' }}" href="{{ $lesson->live_url }}" target="_blank" rel="noopener noreferrer">{!! I::svg('live') !!}{{ $isTeams ? 'Join on Microsoft Teams' : 'Join session' }}</a>
                                        @else
                                            <p class="cl-note">The meeting link is not available yet.</p>
                                        @endif
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                    </div>
                    </div>
                </section>
            @endforeach
        </div>

    @endif
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/zu-course-learn.js') }}?v={{ @filemtime(public_path('js/zu-course-learn.js')) }}"></script>
<script src="{{ asset('js/zu-lightbox.js') }}?v={{ @filemtime(public_path('js/zu-lightbox.js')) }}"></script>
@endpush
