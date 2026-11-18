@extends('management.layout')
@section('title', 'Review Course')
@section('content')
<a class="back-link reveal" style="--i:0" href="{{ route('management.requests') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>Back to requests</a>

<section class="page-hero reveal" style="--i:1">
  <div>
    <span class="eyebrow">Course request · {{ substr($course->offering_id, 0, 8) }}</span>
    <h1>Review course request</h1>
    <p>Check course information before deciding whether to publish.</p>
  </div>
  @include('management.partials.status-badge', ['status' => $course->approval_status])
</section>

<div class="review-layout">
  <div class="review-content">
    <section class="panel glass-card course-header reveal" style="--i:2">
      <x-course-cover :course="$course" variant="thumb" class="review-cover" />
      <div>
        <span class="kicker">Course submission</span>
        <h2>{{ $course->title }}</h2>
        <p class="muted">Submitted by {{ $course->instructor_name }}</p>
        <div class="chips"><span>{{ $course->college_name }}</span><span>{{ $course->topic_name ?? 'General topic' }}</span><span>{{ ucfirst($course->course_status ?? 'ongoing') }}</span></div>
      </div>
    </section>

    <section class="panel glass-card reveal" style="--i:3">
      <header class="panel-head"><div><span class="kicker">Details</span><h2>Course information</h2></div></header>
      <dl class="detail-grid">
        <div><dt>Instructor</dt><dd>{{ $course->instructor_name }}<span>{{ $course->instructor_email }}</span></dd></div>
        <div><dt>College</dt><dd>{{ $course->college_name }}</dd></div>
        <div><dt>Department</dt><dd>{{ $course->department_name }}</dd></div>
        <div><dt>Related university subject</dt><dd>{{ $course->course_name }}</dd></div>
        <div><dt>Topic</dt><dd>{{ $course->topic_name ?? 'General / not set' }}</dd></div>
        <div><dt>Duration</dt><dd>{{ $course->duration_hours !== null ? $course->duration_hours . ' hours' : 'Not specified' }}</dd></div>
        <div><dt>Lecture schedule</dt><dd>{{ $course->lecture_schedule ?: 'Not specified' }}</dd></div>
        <div><dt>Submitted on</dt><dd>{{ $course->submitted_at ?? $course->created_at ?? '—' }}</dd></div>
      </dl>
      <div class="description-block"><small>Course description</small><p>{{ $course->description ?: 'No description provided.' }}</p></div>
    </section>

    @if($course->review_note)
      <section class="panel glass-card note-card reveal" style="--i:4">
        <span class="kicker">Previous feedback</span><h2>Last admin note</h2><p>{{ $course->review_note }}</p>
      </section>
    @endif

    @if($course->approval_status === 'pending')
      <section class="panel glass-card accent-yellow reveal" style="--i:5">
        <header class="panel-head"><div><span class="kicker">Optional corrections</span><h2>Edit administrative details</h2></div></header>
        <p class="muted">Fix simple title or topic mistakes here. Request changes for academic content.</p>
        <form method="post" action="{{ route('management.requests.details', $course->offering_id) }}" class="form-stack" data-submit-once>@csrf
          <div class="field-group"><label for="course-title">Course title</label><input id="course-title" name="title" type="text" value="{{ old('title', $course->title) }}" required maxlength="255"></div>
          <div class="field-group"><label for="topic-id">Topic</label><select id="topic-id" name="topic_id"><option value="">General / not set</option>@foreach($topics as $t)<option value="{{ $t->topic_id }}" @selected(old('topic_id', $course->topic_id) == $t->topic_id)>{{ $t->topic_name }}</option>@endforeach</select></div>
          <div><button class="btn btn-green" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.500 4.500 4.500L19 7.500"/></svg>Save details</button></div>
        </form>
      </section>
    @endif
  </div>

  <aside class="review-side">
    <section class="panel glass-card decision-card reveal" style="--i:3">
      <header class="panel-head"><div><span class="kicker">Admin action</span><h2>Review decision</h2></div></header>
      @if($course->approval_status === 'pending')
        <p class="muted">Choose how to proceed. Your feedback is shown directly on the instructor's Course Requests page.</p>
        <form method="post" action="{{ route('management.requests.decide', $course->offering_id) }}" id="decision-form" class="form-stack" data-submit-once>@csrf
          <div class="field-group">
            <label for="review-note">Feedback to instructor</label>
            <textarea name="review_note" id="review-note" maxlength="3000" placeholder="Write feedback if changes or rejection are needed...">{{ old('review_note') }}</textarea>
            <small>Required for Request Changes and Reject.</small>
            <p class="field-error" id="review-note-error" role="alert" hidden>Please write a note for the instructor before requesting changes or rejecting.</p>
          </div>
          <div class="decision-actions">
            <button type="submit" name="decision" value="approved" class="btn btn-green btn-wide" data-zu-confirm data-confirm-tone="green" data-confirm-icon="check" data-confirm-title="Approve &amp; publish this course?" data-confirm-message="The course goes live in the student catalog right away. This decision cannot be changed afterwards." data-confirm-label="Approve &amp; publish" data-confirm-note-label="Feedback to instructor" data-confirm-note="#review-note"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.500 4.500 4.500L19 7.500"/></svg>Approve &amp; publish</button>
            <button type="submit" name="decision" value="changes_requested" class="btn btn-yellow btn-wide" data-zu-confirm data-confirm-tone="yellow" data-confirm-icon="edit" data-confirm-title="Request changes?" data-confirm-message="The instructor will see your feedback and can update the course before resubmitting." data-confirm-label="Request changes" data-confirm-note-label="Feedback to instructor" data-confirm-requires-note="#review-note"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12a8 8 0 0 1 14-5.300L20 9M20 4v5h-5M20 12a8 8 0 0 1-14 5.300L4 15M4 20v-5h5"/></svg>Request changes</button>
            <button type="submit" name="decision" value="rejected" class="btn btn-red btn-wide" data-zu-confirm data-confirm-tone="red" data-confirm-icon="warning" data-confirm-title="Reject this request?" data-confirm-message="The course will not be published and the instructor will see your reason. This decision cannot be changed afterwards." data-confirm-label="Yes, reject" data-confirm-note-label="Feedback to instructor" data-confirm-requires-note="#review-note"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>Reject request</button>
          </div>
        </form>
      @else
        <div class="decision-complete"><span class="empty-icon tone-green"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.500 4.500 4.500L19 7.500"/></svg></span><strong>Review recorded</strong><p>This request has already been reviewed. The instructor can resubmit a request when changes are requested.</p></div>
      @endif
    </section>
    <div class="review-tip glass reveal" style="--i:4"><strong>Review scope</strong><p>Admin approves course metadata only. Lessons, videos, and materials stay under instructor control.</p></div>
  </aside>
</div>
@endsection
