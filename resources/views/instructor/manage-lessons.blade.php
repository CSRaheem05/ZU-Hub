@extends('layouts.glass')

@php
    use App\Support\LessonIcons as I;
    use Illuminate\Support\Str;

    $kinds = I::kinds();
    $pending = $pending ?? false;
    $count = $sections->sum(fn ($s) => $s->lessons->count());
    $nSections = $sections->count();
    $documentLimit = $documentLimit ?? $files->effectiveMaxBytes('document');
    $videoLimit = $videoLimit ?? $files->effectiveMaxBytes('video');
@endphp

@section('title', 'Course content')
@section('footer_right', 'Zarqa University · Instructor workspace')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/zu-lessons.css') }}?v={{ @filemtime(public_path('css/zu-lessons.css')) }}">
<link rel="stylesheet" href="{{ asset('css/zu-lightbox.css') }}?v={{ @filemtime(public_path('css/zu-lightbox.css')) }}">
@endpush

@section('content')
<div id="lmRoot" data-course="{{ $course->offering_id }}" data-csrf="{{ csrf_token() }}"
     data-layout-url="{{ route('instructor.lessons.layout', $course->offering_id) }}"
     data-sections-url="{{ route('instructor.sections.store', $course->offering_id) }}"
     data-store-url="{{ route('instructor.lessons.store', $course->offering_id) }}"
     data-doc-bytes="{{ $documentLimit }}" data-doc-label="{{ $files->humanSize($documentLimit) }}" data-doc-accept="{{ $files->accept('document') }}"
     data-video-bytes="{{ $videoLimit }}" data-video-label="{{ $files->humanSize($videoLimit) }}" data-video-accept="{{ $files->accept('video') }}"
     data-doc-types="{{ implode(',', array_keys(\App\Services\LessonFileService::DOCUMENT_TYPES)) }}" data-video-types="mp4">

    {{-- ============================================================ heading --}}
    <header class="lm-head reveal" style="--i:0">
        <div class="lm-head__id">
            <a href="{{ route('instructor.courses') }}" class="lm-back" aria-label="Back to My Courses" title="Back to My Courses">{!! I::svg('back') !!}</a>
            <x-course-cover :course="$course" variant="thumb" class="lm-thumb" />
            <div class="lm-head__text">
                <h1>{{ $course->title }}</h1>
                <p id="lmSummary">{{ $nSections }} {{ Str::plural('section', $nSections) }} <span aria-hidden="true">·</span> {{ $count }} {{ Str::plural('item', $count) }}</p>
            </div>
        </div>

        @unless($pending)
            <div class="lm-head__actions">
                @if($count > 0)
                    <a href="{{ route('instructor.lessons.preview', $course->offering_id) }}" class="btn btn-glass" data-lm-preview>{!! I::svg('eye') !!}Student preview</a>
                @endif
                <button type="button" class="btn btn-blue" data-lm-add-section>{!! I::svg('layers') !!}Add section</button>
            </div>
        @endunless
    </header>

    @if($pending)

        <section class="lm-empty reveal" style="--i:1">
            <div class="lm-empty__art" aria-hidden="true"><span class="lm-empty__card lm-empty__card--b lm-k--video">{!! I::svg('layers') !!}</span></div>
            <h2>One quick update is needed</h2>
            <p>Course sections need a one-time database update that has not been applied yet. Ask the site administrator to run it, then reload this page.</p>
        </section>

    @else

        {{-- ======================================================== empty state (no sections yet) --}}
        <section class="lm-empty reveal" style="--i:1" id="lmEmpty" @if($nSections > 0) hidden @endif>
            <div class="lm-empty__art" aria-hidden="true">
                <span class="lm-empty__card lm-empty__card--a lm-k--video">{!! I::svg('video') !!}</span>
                <span class="lm-empty__card lm-empty__card--b lm-k--file-pdf"><b>PDF</b></span>
                <span class="lm-empty__card lm-empty__card--c lm-k--live">{!! I::svg('live') !!}</span>
            </div>
            <h2>Start building your course</h2>
            <p>Organise your course into sections, then add videos, documents, links and live sessions inside each one.</p>
            <button type="button" class="btn btn-blue" data-lm-add-section>{!! I::svg('layers') !!}Add your first section</button>
        </section>

        {{-- ======================================================== the sections --}}
        <div class="lm-sections" id="lmSections" @if($nSections === 0) hidden @endif>
            @foreach($sections as $section)
                @include('instructor.partials.lesson-section', ['section' => $section])
            @endforeach
        </div>

        <template id="lmSectionTpl">
            @include('instructor.partials.lesson-section', ['section' => (object) ['section_id' => '__ID__', 'title' => '__TITLE__', 'lessons' => collect()]])
        </template>

        {{-- ============================================================ add / edit panel --}}
        <div class="lm-panel" id="lmPanel" hidden>
            <div class="lm-panel__backdrop" data-lm-close></div>

            <aside class="lm-sheet" role="dialog" aria-modal="true" aria-labelledby="lmPanelTitle" tabindex="-1">
                <header class="lm-sheet__head">
                    <button type="button" class="lm-icon-btn" id="lmPanelBack" aria-label="Choose a different type" hidden>{!! I::svg('back') !!}</button>
                    <h2 id="lmPanelTitle">Add content</h2>
                    <button type="button" class="lm-icon-btn" data-lm-close aria-label="Close">{!! I::svg('x') !!}</button>
                </header>

                {{-- step 1: what are you adding? --}}
                <div class="lm-pick" id="lmPick">
                    @foreach($kinds as $key => [$label, $hint])
                        <button type="button" class="lm-kind lm-kind--row lm-k--{{ $key }}" data-lm-add="{{ $key }}">
                            <span class="lm-tile">{!! I::svg($key) !!}</span>
                            <span class="lm-kind__text"><strong>{{ $label }}</strong><small>{{ $hint }}</small></span>
                            <svg class="lm-kind__go" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                        </button>
                    @endforeach
                </div>

                {{-- step 2: the details --}}
                <form class="lm-form" id="lmForm" method="POST" action="{{ route('instructor.lessons.store', $course->offering_id) }}" enctype="multipart/form-data" novalidate hidden>
                    @csrf
                    <input type="hidden" name="_method" value="PUT" id="lmMethod" disabled>
                    <input type="hidden" name="lesson_type" id="lmType" value="">

                    <p class="lm-typebadge lm-k--video" id="lmTypeBadge"><span class="lm-tile" id="lmTypeIcon"></span><b id="lmTypeName">Video</b></p>

                    <div class="lm-field lm-field--section" id="lmSectionField">
                        <label for="lmSection">Section</label>
                        <select name="section_id" id="lmSection"></select>
                        <p class="lm-error" data-error-for="section_id" role="alert" hidden></p>
                    </div>

                    <div class="lm-field">
                        <label for="lmTitle">Title <em>*</em></label>
                        <input type="text" name="lesson_title" id="lmTitle" maxlength="255" autocomplete="off" placeholder="e.g. Week 1 · Introduction">
                        <p class="lm-error" data-error-for="lesson_title" role="alert" hidden></p>
                    </div>

                    {{-- external resource: any link, YouTube included --}}
                    <div class="lm-field" id="lmUrlField">
                        <label for="lmUrl" id="lmUrlLabel">Link <em>*</em></label>
                        <input type="url" name="lesson_url" id="lmUrl" inputmode="url" autocomplete="off" placeholder="https://">
                        <p class="lm-help" id="lmUrlHelp">YouTube videos play inside the course. Other links open in a new tab.</p>
                        <p class="lm-error" data-error-for="lesson_url" role="alert" hidden></p>
                    </div>

                    <div class="lm-field" id="lmFileField">
                        <span class="lm-label" id="lmFileLabel"><span id="lmFileLabelText">File</span> <em id="lmFileStar">*</em></span>
                        <div class="lm-drop" id="lmDrop">
                            <input type="file" name="file" id="lmFile" class="lm-drop__input" accept="{{ $files->accept('document') }}" aria-labelledby="lmFileLabel" aria-describedby="lmFileHelp">
                            <label for="lmFile" class="lm-drop__prompt" id="lmDropPrompt">
                                <span class="lm-drop__icon" aria-hidden="true">{!! I::svg('upload') !!}</span>
                                <strong id="lmDropTitle">Drop a file here, or <u>browse</u></strong>
                                <small id="lmFileHelp"></small>
                            </label>
                            <div class="lm-drop__picked" id="lmPicked" hidden>
                                <span class="lm-tile" id="lmPickedIcon" aria-hidden="true"><b>FILE</b></span>
                                <span class="lm-drop__info"><strong id="lmPickedName">File</strong><small id="lmPickedMeta"></small></span>
                                <span class="lm-drop__btns">
                                    <label for="lmFile" class="lm-mini" id="lmPickedChange">Replace</label>
                                    <button type="button" class="lm-mini lm-mini--red" id="lmPickedRemove">Remove</button>
                                </span>
                            </div>
                        </div>
                        <p class="lm-error" data-error-for="file" role="alert" hidden></p>
                    </div>

                    {{-- live: a Microsoft Teams meeting --}}
                    <div class="lm-field" id="lmLiveUrlField">
                        <label for="lmLiveUrl">Microsoft Teams meeting link <em>*</em></label>
                        <input type="url" name="live_url" id="lmLiveUrl" inputmode="url" autocomplete="off" placeholder="https://teams.microsoft.com/…">
                        <p class="lm-help">Copy the join link from your Teams invitation. Students are sent to exactly this link.</p>
                        <p class="lm-error" data-error-for="live_url" role="alert" hidden></p>
                    </div>
                    <div class="lm-field" id="lmWhenField">
                        <label for="lmWhen">Date and time <em>*</em></label>
                        <input type="datetime-local" name="scheduled_at" id="lmWhen">
                        <p class="lm-help">After the session, add the recording as a Video in this course.</p>
                        <p class="lm-error" data-error-for="scheduled_at" role="alert" hidden></p>
                    </div>

                    <div class="lm-field">
                        <label for="lmDesc">Description <span class="lm-opt">optional</span></label>
                        <textarea name="description" id="lmDesc" rows="3" maxlength="2000" placeholder="What will students get from this?"></textarea>
                        <p class="lm-error" data-error-for="description" role="alert" hidden></p>
                    </div>

                    <p class="lm-error lm-error--box" id="lmFormError" role="alert" hidden></p>

                    <div class="lm-progress" id="lmProgress" hidden aria-hidden="true"><i id="lmProgressBar"></i></div>

                    <footer class="lm-form__foot">
                        <button type="button" class="btn btn-glass" data-lm-close>Cancel</button>
                        <button type="submit" class="btn btn-blue" id="lmSubmit">{!! I::svg('check') !!}<span id="lmSubmitLabel">Add content</span></button>
                    </footer>
                </form>
            </aside>
        </div>

        {{-- shared popover for the Move and Section menus --}}
        <div class="lm-pop" id="lmPop" role="menu" hidden></div>
    @endif

    <div class="lm-toast" id="lmToast" role="status" aria-live="polite" hidden></div>
    <p class="lm-sr" id="lmAnnounce" aria-live="polite"></p>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/zu-lessons.js') }}?v={{ @filemtime(public_path('js/zu-lessons.js')) }}"></script>
<script src="{{ asset('js/zu-lightbox.js') }}?v={{ @filemtime(public_path('js/zu-lightbox.js')) }}"></script>
@endpush
