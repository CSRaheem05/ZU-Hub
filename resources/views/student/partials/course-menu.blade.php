{{-- The single quiet secondary action of a library card, tucked into a small menu.
     Needs: $c (a course), $svg (icon helper), $kind: 'leave' (cancel enrolment) | 'unsave' (remove a saved completed course) --}}
@php
    $unsave = $kind === 'unsave';
    $finished = $c->course_status === 'completed';
    $action = $unsave ? route('courses.unsave', $c->offering_id) : route('student.course.unenroll', $c->offering_id);
    $label = $unsave ? ['Remove from saved', 'إزالة من المحفوظات'] : ($finished ? ['Remove from My Courses', 'إزالة من دوراتي'] : ['Leave course', 'إلغاء الاشتراك']);
    $title = $unsave ? ['Remove this saved course?', 'إزالة هذه الدورة المحفوظة؟'] : ($finished ? ['Remove this course?', 'إزالة هذه الدورة؟'] : ['Leave this course?', 'إلغاء الاشتراك في هذه الدورة؟']);
    $message = $unsave
        ? ['“'.$c->title.'” will be removed from your saved courses. You can save it again from the Courses page.', '«'.$c->title.'» ستُزال من دوراتك المحفوظة. يمكنك حفظها مجدداً من صفحة الدورات.']
        : ($finished
            ? ['“'.$c->title.'” will be removed from My Courses. You can still watch it any time from the Courses page.', '«'.$c->title.'» ستُزال من دوراتي. يمكنك مشاهدتها في أي وقت من صفحة الدورات.']
            : ['You will lose access to the lessons of “'.$c->title.'” until you enroll again.', 'ستفقد الوصول إلى دروس «'.$c->title.'» إلى أن تسجّل مجدداً.']);
    $confirm = $unsave ? ['Yes, remove', 'نعم، أزل'] : ($finished ? ['Yes, remove', 'نعم، أزل'] : ['Yes, leave', 'نعم، ألغِ الاشتراك']);
@endphp
<details class="mc-more">
    <summary class="mc-more__btn" aria-label="More options for {{ $c->title }}" title="More options">{!! $svg('more') !!}</summary>
    <div class="mc-more__menu">
        <form method="POST" action="{{ $action }}" data-submit-once>
            @csrf
            @method('DELETE')
            <button type="submit" class="mc-more__item"
                    data-zu-confirm data-confirm-tone="red" data-confirm-icon="warning"
                    data-confirm-title="{{ $title[0] }}" data-confirm-message="{{ $message[0] }}" data-confirm-label="{{ $confirm[0] }}"
                    data-en-title="{{ $title[0] }}" data-ar-title="{{ $title[1] }}"
                    data-en-message="{{ $message[0] }}" data-ar-message="{{ $message[1] }}"
                    data-en-label="{{ $confirm[0] }}" data-ar-label="{{ $confirm[1] }}">
                {!! $svg($unsave ? 'bookmark' : 'leave') !!}<span data-en="{{ $label[0] }}" data-ar="{{ $label[1] }}">{{ $label[0] }}</span>
            </button>
        </form>
    </div>
</details>
