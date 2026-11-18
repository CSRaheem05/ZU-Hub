{{--
    One lesson as students see it. Shared by the student course page and the instructor's Student preview
    (both render student.course-content), so the two can never drift apart.

    Needs: $lesson, $files (LessonFileService). The stored file name is deliberately NOT shown: the instructor's title and
    description are the content; the file name stays in the database and is still what the browser saves a download as.
--}}
@php
    use App\Support\FileKind;
    use App\Support\LessonIcons as I;
    use App\Support\TeamsUrl;
    use App\Support\VideoEmbed;
    use Illuminate\Support\Carbon;

    $type = $lesson->lesson_type;
    $hasFile = ! empty($lesson->file_path);
    $isUpload = $type === 'video' && $hasFile;
    $ext = ($type === 'file' || $isUpload) ? strtolower(pathinfo((string) $lesson->file_name, PATHINFO_EXTENSION)) : null;
    $isImage = $type === 'file' && $hasFile && in_array($lesson->file_mime, ['image/jpeg', 'image/png', 'image/webp'], true);
    $fileUrl = route('lessons.file', ['lessonId' => $lesson->lesson_id]);

    // a YouTube address works as a video whether the instructor filed it as a video or as an external resource
    $embed = in_array($type, ['video', 'link'], true) ? VideoEmbed::resolve($lesson->lesson_url) : ['embed' => null, 'direct' => false];
    $isYoutube = (bool) $embed['embed'];
    $isDirect = $type === 'video' && ! $isUpload && ! $isYoutube && $embed['direct'];

    $form = match (true) {
        $isYoutube => 'youtube',
        $isUpload || $isDirect => 'video',
        $isImage => 'image',
        default => $type,   // file | link | live | video (no usable source)
    };
    // colour family for the whole row: documents by FileKind, everything else by its own form
    $kind = FileKind::familyFor($form, $ext);
    $label = match (true) {
        $isYoutube => 'YouTube',
        $isImage => 'Image',
        $type === 'file' => FileKind::label($ext),
        $type === 'video' => 'Video',
        $type === 'link' => 'External resource',
        default => 'Live session',
    };

    $title = trim((string) $lesson->lesson_title) !== '' ? $lesson->lesson_title : $label;
    $desc = trim((string) ($lesson->description ?? ''));
    $when = $type === 'live' && $lesson->scheduled_at ? Carbon::parse($lesson->scheduled_at) : null;
    $size = $type === 'file' && $lesson->file_size ? $files->humanSize((int) $lesson->file_size) : null;
    $host = $type === 'link' && $lesson->lesson_url ? preg_replace('/^www\./', '', (string) parse_url($lesson->lesson_url, PHP_URL_HOST)) : null;

    // YouTube's own poster frame (loaded only once the section is opened); nothing is fetched for uploaded videos
    $poster = $isYoutube ? 'https://i.ytimg.com/vi/'.basename($embed['embed']).'/mqdefault.jpg' : null;
    $playSrc = $isYoutube ? $embed['embed'] : ($isUpload ? $fileUrl.'?view=1' : ($isDirect ? $lesson->lesson_url : null));
    $playAttr = $playSrc ? 'data-cl-play="'.($isYoutube ? 'youtube' : 'video').'" data-src="'.e($playSrc).'" data-title="'.e($title).'"' : '';
@endphp

<article class="cl-item fk-{{ $kind }} cl-f--{{ $form }}" id="lesson-{{ $lesson->lesson_id }}">

    {{-- ------------------------------------------------------------ visual --}}
    @if($isImage)
        <button type="button" class="cl-thumb cl-thumb--square" data-zu-lightbox="{{ $fileUrl }}?view=1" data-caption="{{ $title }}" aria-label="View image “{{ $title }}” full size">
            {!! I::svg('image', 'cl-thumb__fallback') !!}
            <img data-src="{{ $fileUrl }}?view=1" alt="" width="64" height="64" decoding="async" onerror="this.remove()">
            <span class="cl-thumb__badge" aria-hidden="true">{!! I::svg('expand') !!}</span>
        </button>
    @elseif($form === 'youtube' || $form === 'video')
        <button type="button" class="cl-thumb cl-thumb--wide" {!! $playAttr !!} aria-label="Watch “{{ $title }}”">
            @if($poster)<img data-src="{{ $poster }}" alt="" width="112" height="63" decoding="async" referrerpolicy="no-referrer" onerror="this.remove()">@endif
            <span class="cl-thumb__play" aria-hidden="true">{!! I::svg('play') !!}</span>
            @if($isYoutube)<span class="cl-thumb__yt" aria-hidden="true">{!! I::svg('play') !!}</span>@endif
        </button>
    @else
        <span class="fk-tile" aria-hidden="true">{!! FileKind::tile($type === 'video' ? 'video' : $form, $ext) !!}</span>
    @endif

    {{-- -------------------------------------------------------------- text --}}
    <div class="cl-item__body">
        <h3>{{ $title }}</h3>
        <p class="cl-item__meta">
            <span class="fk-kind">@if($isYoutube)<i class="cl-yt" aria-hidden="true">{!! I::svg('play') !!}</i>@elseif($type === 'live')<i class="cl-dot" aria-hidden="true"></i>@endif{{ $label }}</span>
            @if($size)<span>{{ $size }}</span>@endif
            @if($host)<span>{{ $host }}</span>@endif
            @if($when)<span>{{ $when->format('D, M j · g:i A') }}</span><span class="cl-when {{ $when->isFuture() ? 'is-soon' : '' }}">{{ $when->isFuture() ? 'Upcoming' : 'Session time has passed' }}</span>
            @elseif($type === 'live')<span>Time to be announced</span>@endif
        </p>
        @if($desc !== '')
            <p class="cl-item__desc" data-cl-desc>{{ $desc }}</p>
        @endif
    </div>

    {{-- ----------------------------------------------------------- actions --}}
    <div class="cl-item__actions">
        @if($form === 'youtube' || $form === 'video')
            <button type="button" class="cl-btn cl-btn--play" {!! $playAttr !!}>{!! I::svg('play') !!}Watch</button>
        @elseif($form === 'image')
            <button type="button" class="cl-btn" data-zu-lightbox="{{ $fileUrl }}?view=1" data-caption="{{ $title }}">{!! I::svg('eye') !!}View</button>
            <a class="cl-btn cl-btn--ghost cl-btn--icon" href="{{ $fileUrl }}" aria-label="Download “{{ $title }}”" title="Download">{!! I::svg('download') !!}<span>Download</span></a>
        @elseif($type === 'file')
            @if($hasFile)
                @if($lesson->file_mime === 'application/pdf')
                    <a class="cl-btn" href="{{ $fileUrl }}?view=1" target="_blank" rel="noopener">{!! I::svg('eye') !!}View</a>
                    <a class="cl-btn cl-btn--ghost cl-btn--icon" href="{{ $fileUrl }}" aria-label="Download “{{ $title }}”" title="Download">{!! I::svg('download') !!}<span>Download</span></a>
                @else
                    <a class="cl-btn" href="{{ $fileUrl }}" aria-label="Download “{{ $title }}”">{!! I::svg('download') !!}Download</a>
                @endif
            @else
                <span class="cl-note">Not available yet</span>
            @endif
        @elseif($type === 'link')
            @if($lesson->lesson_url)
                <a class="cl-btn" href="{{ $lesson->lesson_url }}" target="_blank" rel="noopener noreferrer">{!! I::svg('external') !!}Open link</a>
            @else
                <span class="cl-note">Not available yet</span>
            @endif
        @elseif($type === 'live')
            @if($lesson->live_url)
                @php $isTeams = TeamsUrl::isValid($lesson->live_url); @endphp
                <a class="cl-btn {{ $isTeams ? 'cl-btn--teams' : '' }}" href="{{ $lesson->live_url }}" target="_blank" rel="noopener noreferrer">{!! I::svg('live') !!}{{ $isTeams ? 'Join on Teams' : 'Join session' }}</a>
            @else
                <span class="cl-note">Link not available yet</span>
            @endif
        @elseif($type === 'video')
            @if($lesson->lesson_url)
                <a class="cl-btn" href="{{ $lesson->lesson_url }}" target="_blank" rel="noopener noreferrer">{!! I::svg('external') !!}Open video</a>
            @else
                <span class="cl-note">Not available yet</span>
            @endif
        @endif
    </div>
</article>
