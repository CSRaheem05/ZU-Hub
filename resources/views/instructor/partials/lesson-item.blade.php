{{-- One piece of content inside a section. Needs: $lesson, $course, $locked (ids with student progress), $files --}}
@php
    use App\Support\FileKind;
    use App\Support\LessonIcons as I;
    use Illuminate\Support\Carbon;

    $type = \App\Services\LessonService::kindOf($lesson);   // an older "video" that is only a link counts as an external resource
    $fileName = $lesson->file_name ?? null;
    $filePath = $lesson->file_path ?? null;
    $fileSize = $lesson->file_size ?? null;
    $isUpload = $type === 'video' && ! empty($filePath);
    $ext = ($type === 'file' || $isUpload) ? strtolower(pathinfo((string) ($fileName ?: $filePath), PATHINFO_EXTENSION)) : null;
    $isImage = $type === 'file' && in_array($lesson->file_mime ?? '', ['image/jpeg', 'image/png', 'image/webp'], true);   // the formats the file route may show inline
    $isYoutube = $type === 'link' && \App\Support\VideoEmbed::resolve($lesson->lesson_url)['embed'] !== null;
    // the same type identity the students see (FileKind + zu-filekinds.css)
    $form = match (true) {
        $isImage => 'image',
        $isYoutube => 'youtube',
        default => $type,
    };
    $kindClass = FileKind::familyFor($form, $ext);
    $label = match (true) {
        $isImage => 'Image',
        $type === 'file' => FileKind::label($ext),
        $type === 'video' => 'Video',
        $isYoutube => 'YouTube',
        $type === 'link' => 'Resource',
        default => 'Live session',
    };
    $desc = trim((string) ($lesson->description ?? ''));
    $isLocked = in_array($lesson->lesson_id, $locked, true);
    $host = $type === 'link' ? preg_replace('/^www\./', '', (string) parse_url((string) $lesson->lesson_url, PHP_URL_HOST)) : null;
    $when = $type === 'live' && $lesson->scheduled_at ? Carbon::parse($lesson->scheduled_at) : null;
    $liveHost = $type === 'live' ? (\App\Support\TeamsUrl::isValid($lesson->live_url) ? 'Microsoft Teams' : preg_replace('/^www\./', '', (string) parse_url((string) $lesson->live_url, PHP_URL_HOST))) : null;
    $payload = [
        'id' => $lesson->lesson_id, 'type' => $type, 'title' => $lesson->lesson_title, 'description' => $desc,
        'section_id' => $lesson->section_id ?? null,
        'url' => $lesson->lesson_url, 'live_url' => $lesson->live_url,
        'scheduled_at' => $when?->format('Y-m-d\TH:i'),
        'file_name' => $fileName, 'file_size' => $fileSize ? $files->humanSize((int) $fileSize) : null,
        'update_url' => route('instructor.lessons.update', [$course->offering_id, $lesson->lesson_id]),
    ];
@endphp
<li class="lm-item fk-{{ $kindClass }}" id="lesson-{{ $lesson->lesson_id }}" data-id="{{ $lesson->lesson_id }}">
    <button type="button" class="lm-grip" aria-label="Reorder “{{ $lesson->lesson_title }}”. Drag it, or focus it and use the up and down arrow keys." data-hint="Drag to reorder">{!! I::svg('grip') !!}</button>

    @if($isImage)
        <button type="button" class="zu-thumb" data-zu-lightbox="{{ route('lessons.file', ['lessonId' => $lesson->lesson_id]) }}?view=1" data-caption="{{ $lesson->lesson_title }}" aria-label="View image “{{ $lesson->lesson_title }}” full size"><img src="{{ route('lessons.file', ['lessonId' => $lesson->lesson_id]) }}?view=1" alt="" width="56" height="56" loading="lazy" decoding="async"></button>
    @else
        <span class="fk-tile" aria-hidden="true">{!! FileKind::tile($form, $ext) !!}</span>
    @endif

    <div class="lm-item__body">
        <div class="lm-item__top">
            <h3 class="lm-item__title">{{ $lesson->lesson_title }}</h3>
            <span class="lm-chip">@if($type === 'live')<i aria-hidden="true"></i>@endif{{ $label }}</span>
        </div>

        @if($desc !== '')<p class="lm-item__desc">{{ $desc }}</p>@endif

        <p class="lm-item__meta">
            @if($type === 'file' || $isUpload)
                {!! I::svg($isImage ? 'image' : ($type === 'file' ? 'file' : 'video')) !!}<span>{{ $fileName ?: 'File' }}@if($fileSize) · {{ $files->humanSize((int) $fileSize) }}@endif</span>
            @elseif($type === 'live')
                {!! I::svg('clock') !!}<span>{{ $when ? $when->format('D, M j · g:i A') : 'Time not set' }}@if($liveHost) · {{ $liveHost }}@endif</span>
            @elseif($host)
                {!! I::svg('globe') !!}<span>{{ $host }}</span>
            @endif
        </p>
    </div>

    <div class="lm-item__actions">
        <button type="button" class="lm-act" data-lm-edit="{{ json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }}" aria-label="Edit “{{ $lesson->lesson_title }}”">{!! I::svg('edit') !!}<span>Edit</span></button>

        <form method="POST" action="{{ route('instructor.lessons.destroy', [$course->offering_id, $lesson->lesson_id]) }}" data-submit-once>
            @csrf @method('DELETE')
            @if($isLocked)
                <button type="button" class="lm-act lm-act--red is-locked" disabled aria-label="Cannot delete: students have progress on this lesson" title="Students have progress on this lesson, so it can’t be deleted.">{!! I::svg('lock') !!}<span>Delete</span></button>
            @else
                <button type="submit" class="lm-act lm-act--red" aria-label="Delete “{{ $lesson->lesson_title }}”"
                        data-zu-confirm data-confirm-tone="red" data-confirm-icon="warning"
                        data-confirm-title="Delete this content?"
                        data-confirm-message="“{{ $lesson->lesson_title }}” will be permanently deleted{{ ($type === 'file' || $isUpload) ? ' together with its file' : '' }}. This cannot be undone."
                        data-confirm-label="Yes, delete">{!! I::svg('trash') !!}<span>Delete</span></button>
            @endif
        </form>
    </div>
</li>
