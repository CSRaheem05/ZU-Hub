{{-- One course section (a collapsible glass accordion). Needs: $section (->section_id, ->title, ->lessons), $course, $locked, $files --}}
@php
    use App\Support\LessonIcons as I;
    $n = $section->lessons->count();
@endphp
<section class="lm-section" data-section-id="{{ $section->section_id }}" id="section-{{ $section->section_id }}">
    <header class="lm-section__head">
        <button type="button" class="lm-shandle" data-lm-section-grip aria-label="Reorder section “{{ $section->title }}”. Drag it, or focus it and use the up and down arrow keys." data-hint="Drag to reorder">{!! I::svg('grip') !!}</button>

        <button type="button" class="lm-section__toggle" aria-expanded="true" aria-controls="sb-{{ $section->section_id }}">
            {!! I::svg('chevron', 'lm-section__chev') !!}
            <span class="lm-section__title">{{ $section->title }}</span>
            <span class="lm-section__count">{{ $n === 0 ? 'Empty' : $n.' '.($n === 1 ? 'item' : 'items') }}</span>
        </button>

        <div class="lm-section__tools">
            <button type="button" class="lm-soft" data-lm-add-in aria-label="Add content to “{{ $section->title }}”">{!! I::svg('plus') !!}<span>Add content</span></button>
            <button type="button" class="lm-icon-btn" data-lm-section-menu aria-haspopup="menu" aria-expanded="false" aria-label="Section options for “{{ $section->title }}”">{!! I::svg('more') !!}</button>
        </div>
    </header>

    <div class="lm-section__body" id="sb-{{ $section->section_id }}">
        <ol class="lm-list" data-section-list>
            @foreach($section->lessons as $lesson)
                @include('instructor.partials.lesson-item', ['lesson' => $lesson])
            @endforeach
        </ol>
        <div class="lm-section__empty">
            <span>Nothing here yet.</span>
            <button type="button" class="lm-link" data-lm-add-in>Add content</button>
            <span>or drag items from another section.</span>
        </div>
    </div>
</section>
