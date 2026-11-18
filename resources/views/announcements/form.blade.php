@extends('layouts.glass')
@php
  $editing = $announcement->exists;
  $isAdmin = auth()->user()->role === 'manager';
  $published = $editing && $announcement->isPublished();
  $audienceValue = old('audience', $announcement->audience ?? array_key_first($audiences));
  $priorityValue = old('priority', $announcement->priority ?? 'normal');
  $startLocked = $editing && $announcement->isPublished() && ! $announcement->isScheduled(); // already live
  $sendMode = old('send_mode', ($announcement->publish_at?->isFuture() ?? false) ? 'later' : 'now');
  $expireMode = old('expire_mode', $announcement->expires_at ? 'date' : 'never');
  $startIso = old('publish_at', $announcement->publish_at?->toIso8601String());
  $endIso = old('expires_at', $announcement->expires_at?->toIso8601String());
  $priorityHints = ['normal' => 'Everyday news', 'important' => 'Needs attention', 'urgent' => 'Act immediately'];
@endphp
@section('title', $editing ? 'Edit announcement' : 'New announcement')
@section('content')
<a class="back-link reveal" style="--i:0" href="{{ route('announcements.index') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>All announcements</a>

<section class="page-hero reveal" style="--i:1">
  <div>
    <span class="eyebrow">{{ $isAdmin ? 'Official communication' : 'Instructor workspace' }}</span>
    <h1>{{ $editing ? 'Edit announcement' : 'New announcement' }}</h1>
    <p>{{ $published ? 'Changes are shown to everyone who already received it.' : 'Write it, preview it, then save a draft or publish.' }}</p>
  </div>
</section>

<div class="review-layout">
  <form method="POST" id="announcement-form" class="panel glass-card form-stack reveal" style="--i:2"
        action="{{ $editing ? route('announcements.update', $announcement) : route('announcements.store') }}" data-submit-once>
    @csrf
    @if($editing) @method('PUT') @endif

    <div class="field-group">
      <label for="ann-title">Title</label>
      <input id="ann-title" name="title" type="text" maxlength="160" required value="{{ old('title', $announcement->title) }}" placeholder="A short, clear headline">
    </div>

    <div class="field-group">
      <label for="ann-body">Message</label>
      <textarea id="ann-body" name="body" required maxlength="5000" style="min-height:190px" placeholder="Write your announcement…">{{ old('body', $announcement->body) }}</textarea>
      <small><span id="ann-count">0</span> / 5000</small>
    </div>

    {{-- Audience: fixed once the announcement exists, so recipients never drift. --}}
    <fieldset class="field-group choice-group" @if($editing) disabled @endif>
      <legend>Send to</legend>
      @if($isAdmin)
        <div class="choice-row">
          @foreach($audiences as $key => $label)
            <label class="choice"><input type="radio" name="audience" value="{{ $key }}" @checked($audienceValue === $key)><span><b>{{ $label }}</b><small>{{ ['all' => 'Every student and instructor', 'students' => 'All students', 'instructors' => 'All instructors'][$key] }}</small></span></label>
          @endforeach
        </div>
        @if($editing)@endif
      @else
        <div class="choice-row">
          <label class="choice"><input type="radio" name="audience" value="course" @checked($audienceValue === 'course')><span><b>One of my courses</b><small>Students enrolled in a single course</small></span></label>
          <label class="choice"><input type="radio" name="audience" value="my_students" @checked($audienceValue === 'my_students')><span><b>All my students</b><small>Every student in any of my courses</small></span></label>
        </div>
        <div class="field-group" id="course-picker" @if($audienceValue !== 'course') hidden @endif>
          <label for="ann-offering">Course</label>
          <select id="ann-offering" name="offering_id" @if($audienceValue === 'course' && ! $editing) required @endif>
            <option value="">Choose a course…</option>
            @foreach($offerings as $o)
              <option value="{{ $o->offering_id }}" @selected(old('offering_id', $announcement->offering_id) == $o->offering_id)>{{ $o->title }} · {{ $o->students }} {{ \Illuminate\Support\Str::plural('student', $o->students) }}</option>
            @endforeach
          </select>
          @if($offerings->isEmpty())<small>You have no approved courses yet. Once a course is approved and students join, you can message them here.</small>@endif
        </div>
      @endif
      @if($editing)<small>The audience is locked after creation. Delete and recreate it to change who receives it.</small>@endif
    </fieldset>

    <fieldset class="field-group choice-group">
      <legend>Priority</legend>
      <div class="choice-row">
        @foreach($priorities as $key => $label)
          <label class="choice choice--{{ $key }}"><input type="radio" name="priority" value="{{ $key }}" @checked($priorityValue === $key)><span><b>{{ $label }}</b><small>{{ $priorityHints[$key] }}</small></span></label>
        @endforeach
      </div>
    </fieldset>

    @if($isAdmin)
    {{-- Schedule: the pickers use the browser's local time; hidden fields carry UTC to the server, which re-validates everything. --}}
    <fieldset class="field-group choice-group schedule" id="schedule" data-start="{{ $startIso }}" data-end="{{ $endIso }}">
      <legend>When</legend>
      <div class="when-grid">
        <div class="when-col">
          <span class="when-label">Starts</span>
          @if($startLocked)
            <p class="muted">Live since <time data-local datetime="{{ $announcement->goesLiveAt()?->toIso8601String() }}">{{ $announcement->goesLiveAt()?->format('M d, Y · H:i') }} UTC</time>. The start time can no longer change.</p>
          @else
            <div class="choice-row choice-row--tight">
              <label class="choice"><input type="radio" name="send_mode" value="now" @checked($sendMode === 'now')><span><b>Immediately</b><small>As soon as you publish</small></span></label>
              <label class="choice"><input type="radio" name="send_mode" value="later" @checked($sendMode === 'later')><span><b>Schedule</b><small>Pick a date and time</small></span></label>
            </div>
            <div class="field-group" id="start-picker" @if($sendMode !== 'later') hidden @endif>
              <label for="ann-start">Goes live on</label>
              <input type="datetime-local" id="ann-start">
            </div>
            <input type="hidden" name="publish_at" id="ann-publish-at" value="{{ $startIso }}">
          @endif
        </div>
        <div class="when-col">
          <span class="when-label">Ends</span>
          <div class="choice-row choice-row--tight">
            <label class="choice"><input type="radio" name="expire_mode" value="never" @checked($expireMode === 'never')><span><b>Never</b><small>Stays active</small></span></label>
            <label class="choice"><input type="radio" name="expire_mode" value="date" @checked($expireMode === 'date')><span><b>On a date</b><small>Stops being an alert</small></span></label>
          </div>
          <div class="field-group" id="end-picker" @if($expireMode !== 'date') hidden @endif>
            <label for="ann-end">Expires on</label>
            <input type="datetime-local" id="ann-end">
          </div>
          <input type="hidden" name="expires_at" id="ann-expires-at" value="{{ $endIso }}">
        </div>
      </div>
      <small>Important and Urgent announcements also appear as a banner for students and instructors until they expire. Expired announcements stay in their notification history.</small>
    </fieldset>
    @endif

    <div class="form-actions">
      @if($published)
        <button type="submit" class="btn btn-green"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.500 4.500 4.500L19 7.500"/></svg>Save changes</button>
      @else
        <button type="submit" name="action" value="draft" class="btn btn-green"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.500 4.500 4.500L19 7.500"/></svg>Save draft</button>
        <button type="submit" name="action" value="publish" class="btn btn-blue" data-zu-confirm data-confirm-tone="green" data-confirm-icon="check"
          data-confirm-title="Publish this announcement?" data-confirm-message="It will be delivered right away to the audience you selected." data-confirm-label="Publish now"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 3 10 14M21 3l-7 18-4-7-7-4z"/></svg>Publish now</button>
      @endif
      <a class="btn btn-glass" href="{{ route('announcements.index') }}">Cancel</a>
    </div>
  </form>

  <aside class="review-side">
    <div class="panel glass-card reveal" style="--i:3">
      <header class="panel-head tight"><div><span class="kicker">Live preview</span><h2>How it will look</h2></div></header>
      <div id="ann-preview" class="zn-item {{ $isAdmin ? 'src-official' : 'src-instructor' }} pri-{{ $priorityValue }}" data-official="{{ $isAdmin ? 1 : 0 }}" data-initial="{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}" data-sender="{{ $isAdmin ? 'ZU Administration' : auth()->user()->name }}" style="cursor:default">
        <span class="zn-avatar" id="pv-avatar" aria-hidden="true"></span>
        <span class="zn-body">
          <span class="zn-meta"><span class="zn-sender" id="pv-sender"></span><span class="zn-chip {{ $isAdmin ? 'zn-chip--official' : 'zn-chip--instructor' }}">{{ $isAdmin ? 'Official' : 'Instructor' }}</span><span class="zn-chip" id="pv-chip" hidden></span></span>
          <strong class="zn-title" id="pv-title"></strong>
          <span class="zn-excerpt" id="pv-text"></span>
          <span class="zn-foot"><span id="pv-when">Just now</span></span>
        </span>
        <span class="zn-state"><i class="zn-dot"></i></span>
      </div>
      <p class="muted" style="margin-top:14px">{{ $isAdmin ? 'Important and Urgent announcements are highlighted in red, shown once as a banner, and make the bell glow until they are read.' : 'Your announcements use a violet identity, so students can tell them apart from official university notices.' }}</p>
    </div>
  </aside>
</div>
@endsection

@push('scripts')
<script>
(function () {
  var form = document.getElementById('announcement-form');
  var title = document.getElementById('ann-title'), body = document.getElementById('ann-body');
  var pv = document.getElementById('ann-preview');
  var official = pv.dataset.official === '1';
  var shield = '<svg viewBox="0 0 24 24"><path d="M12 3 4.5 6v5.5c0 4.5 3.1 8.2 7.5 9.5 4.4-1.3 7.5-5 7.5-9.5V6z"/><path d="m9 12 2.2 2.2L15.5 10"/></svg>';
  var alertShield = '<svg viewBox="0 0 24 24"><path d="M12 3 4.5 6v5.5c0 4.5 3.1 8.2 7.5 9.5 4.4-1.3 7.5-5 7.5-9.5V6z"/><path d="M12 8.5v4M12 15.6v.4"/></svg>';

  function priority() { var r = form.querySelector('input[name="priority"]:checked'); return r ? r.value : 'normal'; }
  function render() {
    var p = priority();
    pv.className = 'zn-item is-unread ' + (official ? 'src-official' : 'src-instructor') + ' pri-' + p;
    document.getElementById('pv-avatar').innerHTML = official ? (p === 'normal' ? shield : alertShield) : pv.dataset.initial;
    document.getElementById('pv-sender').textContent = pv.dataset.sender;
    document.getElementById('pv-title').textContent = title.value.trim() || 'Your headline appears here';
    document.getElementById('pv-text').textContent = body.value.replace(/\s+/g, ' ').trim().slice(0, 110) || 'The first lines of your message appear here.';
    var chip = document.getElementById('pv-chip');
    chip.hidden = p === 'normal';
    chip.className = 'zn-chip zn-chip--' + p;
    chip.textContent = p.charAt(0).toUpperCase() + p.slice(1);
    document.getElementById('ann-count').textContent = body.value.length;
  }
  form.addEventListener('input', render); form.addEventListener('change', render); render();

  // Schedule (administrators): local pickers <-> UTC hidden fields, preview line, sensible limits.
  var sched = document.getElementById('schedule');
  if (sched) {
    var pad = function (n) { return String(n).padStart(2, '0'); };
    var toLocalInput = function (iso) { if (!iso) return ''; var d = new Date(iso); return isNaN(d) ? '' : d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes()); };
    var startIn = document.getElementById('ann-start'), endIn = document.getElementById('ann-end');
    var startHidden = document.getElementById('ann-publish-at'), endHidden = document.getElementById('ann-expires-at');
    var startPicker = document.getElementById('start-picker'), endPicker = document.getElementById('end-picker');
    var mode = function (name) { var r = form.querySelector('input[name="' + name + '"]:checked'); return r ? r.value : null; };
    var fmt = function (v) { var d = new Date(v); return isNaN(d) ? '' : d.toLocaleString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }); };

    if (startIn) startIn.value = toLocalInput(sched.dataset.start);
    if (endIn) endIn.value = toLocalInput(sched.dataset.end);
    var nowLocal = toLocalInput(new Date().toISOString());
    if (startIn) startIn.min = nowLocal;
    if (endIn) endIn.min = nowLocal;

    function syncSchedule() {
      var later = mode('send_mode') === 'later', ends = mode('expire_mode') === 'date';
      if (startPicker) { startPicker.hidden = !later; startIn.required = later; }
      endPicker.hidden = !ends; endIn.required = ends;
      if (startIn && later && startIn.value) endIn.min = startIn.value;
      if (startHidden) startHidden.value = later && startIn.value ? new Date(startIn.value).toISOString() : '';
      endHidden.value = ends && endIn.value ? new Date(endIn.value).toISOString() : '';
      var bits = [startHidden ? (later && startIn.value ? 'Goes live ' + fmt(startIn.value) : 'Starts when published') : 'Live now'];
      bits.push(ends && endIn.value ? 'ends ' + fmt(endIn.value) : 'never expires');
      document.getElementById('pv-when').textContent = bits.join(' · ');
    }
    form.addEventListener('input', syncSchedule); form.addEventListener('change', syncSchedule); syncSchedule();
  }

  // Instructors: the course picker only matters for "One of my courses".
  var picker = document.getElementById('course-picker');
  if (picker) {
    var select = picker.querySelector('select');
    form.querySelectorAll('input[name="audience"]').forEach(function (r) {
      r.addEventListener('change', function () {
        var course = form.querySelector('input[name="audience"]:checked').value === 'course';
        picker.hidden = !course; select.required = course && !select.disabled;
      });
    });
  }
})();
</script>
@endpush
