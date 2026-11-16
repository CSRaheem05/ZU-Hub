@extends('management.layout')
@section('title', 'Review Course')
@section('breadcrumb', 'Review request')
@section('content')
<div class="review-back"><a href="{{ route('management.requests') }}">← Back to requests</a></div>
<div class="page-heading review-heading"><div><div class="eyebrow">COURSE REQUEST / {{ substr($course->offering_id,0,8) }}</div><h1>Review course request</h1><p>Check course information before deciding whether to publish.</p></div><span class="status-badge status-{{ $course->approval_status }}"><span class="status-dot"></span>{{ ['pending'=>'Pending review','changes_requested'=>'Needs changes','approved'=>'Approved','rejected'=>'Rejected'][$course->approval_status] ?? ucfirst(str_replace('_',' ',$course->approval_status)) }}</span></div>
<div class="review-layout"><div class="review-content">
  <section class="content-card review-course-header"><div class="large-course-icon">▤</div><div><div class="section-kicker">COURSE SUBMISSION</div><h2>{{ $course->title }}</h2><p>Submitted by {{ $course->instructor_name }}</p><div class="meta-chips"><span>{{ $course->college_name }}</span><span>{{ $course->topic_name ?? 'General topic' }}</span><span>{{ ucfirst($course->course_status ?? 'ongoing') }}</span></div></div></section>
  <section class="content-card review-details"><div class="card-title-row"><div><div class="section-kicker">DETAILS</div><h2>Course information</h2></div><span class="section-icon">☷</span></div><div class="detail-grid">
    <div><small>Instructor</small><strong>{{ $course->instructor_name }}</strong><span>{{ $course->instructor_email }}</span></div>
    <div><small>College</small><strong>{{ $course->college_name }}</strong></div>
    <div><small>Department</small><strong>{{ $course->department_name }}</strong></div>
    <div><small>Related university subject</small><strong>{{ $course->course_name }}</strong></div>
    <div><small>Topic</small><strong>{{ $course->topic_name ?? 'General / not set' }}</strong></div>
    <div><small>Duration</small><strong>{{ $course->duration_hours !== null ? $course->duration_hours . ' hours' : 'Not specified' }}</strong></div>
    <div><small>Lecture schedule</small><strong>{{ $course->lecture_schedule ?: 'Not specified' }}</strong></div>
    <div><small>Submitted on</small><strong>{{ $course->submitted_at ?? $course->created_at ?? '—' }}</strong></div>
  </div><div class="description-block"><small>Course description</small><p>{{ $course->description ?: 'No description provided.' }}</p></div></section>
  @if($course->review_note)<section class="content-card feedback-card"><div class="section-kicker">PREVIOUS FEEDBACK</div><h2>Last admin note</h2><p>{{ $course->review_note }}</p></section>@endif
  @if($course->approval_status==='pending')
  <section class="content-card"><div class="section-kicker">OPTIONAL CORRECTIONS</div><h2>Edit administrative details</h2><p class="section-description">Fix simple title or topic mistakes here. Request changes for academic content.</p><form method="post" action="{{ route('management.requests.details',$course->offering_id) }}">@csrf<div class="field-group"><label for="course-title">Course title</label><input id="course-title" name="title" type="text" value="{{ old('title',$course->title) }}" required maxlength="255"></div><div class="field-group"><label for="topic-id">Topic</label><select id="topic-id" name="topic_id"><option value="">General / not set</option>@foreach($topics as $t)<option value="{{ $t->topic_id }}" @selected(old('topic_id',$course->topic_id)==$t->topic_id)>{{ $t->topic_name }}</option>@endforeach</select></div><button class="btn btn-outline" type="submit">Save details</button></form></section>
  @endif
</div><aside class="review-side">
  <section class="content-card decision-card"><div class="section-kicker">ADMIN ACTION</div><h2>Review decision</h2>
  @if($course->approval_status==='pending')
    <p class="section-description">Choose how to proceed. Your feedback is shown directly in the instructor's Course Requests page.</p>
    <form method="post" action="{{ route('management.requests.decide',$course->offering_id) }}" id="decision-form">@csrf<div class="field-group"><label for="review-note">Feedback to instructor</label><textarea name="review_note" id="review-note" maxlength="3000" placeholder="Write feedback if changes or rejection are needed...">{{old('review_note')}}</textarea><small>Required for Request Changes and Reject.</small></div>
      <button type="submit" name="decision" value="approved" class="btn btn-primary btn-wide" data-confirm="Approve and publish this course?">✓ Approve & publish</button>
      <button type="submit" name="decision" value="changes_requested" class="btn btn-amber btn-wide" data-confirm="Request changes from the instructor?">↺ Request changes</button>
      <button type="submit" name="decision" value="rejected" class="btn btn-danger-outline btn-wide" data-confirm="Reject this course request?">✕ Reject request</button>
    </form>
  @else
    <div class="decision-complete"><span>✓</span><strong>Review recorded</strong><p>This request has already been reviewed. The instructor can resubmit a request when changes are requested.</p></div>
  @endif
  </section><div class="review-tip"><strong>Review scope</strong><p>Admin approves course metadata only. Lessons, videos, and materials stay under instructor control.</p></div>
</aside></div>
@endsection
@push('scripts')
<script>
(function(){var form=document.getElementById('decision-form');if(!form)return;form.querySelectorAll('button[data-confirm]').forEach(function(button){button.addEventListener('click',function(e){var needsNote=button.value!=='approved';var note=document.getElementById('review-note');if(needsNote&&!note.value.trim()){e.preventDefault();note.setCustomValidity('Please provide a reason for this decision.');note.reportValidity();note.focus();return;}note.setCustomValidity('');if(!window.confirm(button.getAttribute('data-confirm')))e.preventDefault();});});var note=document.getElementById('review-note');note.addEventListener('input',function(){note.setCustomValidity('');});})();
</script>
@endpush
