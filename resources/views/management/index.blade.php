@extends('management.layout')
@section('title', 'Admin Dashboard')
@section('content')
@php
  use Illuminate\Support\Carbon;
  use Illuminate\Support\Str;
  $pending = $counts['pending'];
  $submitted = array_sum($counts);
  $pct = fn ($n) => $submitted > 0 ? round($n / $submitted * 100, 1) : 0;
  $hiddenCount = max($pending - $pendingQueue->count(), 0);
  $oldest = $pendingQueue->first();
  $outcomes = [
    ['key' => 'approved',          'label' => 'Approved',      'tone' => 'green'],
    ['key' => 'changes_requested', 'label' => 'Needs changes', 'tone' => 'yellow'],
    ['key' => 'rejected',          'label' => 'Rejected',      'tone' => 'red'],
  ];
@endphp

<section class="page-hero reveal" style="--i:0">
  <div>
    <span class="eyebrow">Administration</span>
    <h1>Admin Dashboard</h1>
    <p>{{ $pending > 0 ? 'Course submissions are waiting for your review.' : 'Everything is reviewed — nothing needs your attention.' }}</p>
  </div>
</section>

<div class="dash-grid">
  {{-- Pending requests: the main task of the dashboard --}}
  <section class="panel glass-card pending-panel {{ $pending > 0 ? 'has-pending' : '' }} reveal" style="--i:1" aria-labelledby="pending-title">
    <a class="pending-head" href="{{ route('management.requests', ['status' => 'pending']) }}">
      <strong class="pending-count" data-count="{{ $pending }}">{{ number_format($pending) }}</strong>
      <span class="pending-title">
        <span class="kicker">Needs your attention</span>
        <span class="pending-heading" id="pending-title">Pending {{ Str::plural('request', $pending) }}</span>
        <small>
          @if($oldest) Oldest submitted {{ Carbon::parse($oldest->created_at)->diffForHumans() }}
          @else No course is waiting for approval @endif
        </small>
      </span>
      <span class="go-arrow" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
    </a>

    @if($pendingQueue->isNotEmpty())
      <div class="queue">
        @foreach($pendingQueue as $item)
          <a class="queue-row" href="{{ route('management.requests.show', $item->offering_id) }}" style="--i:{{ $loop->index }}">
            <span class="mini-avatar">{{ strtoupper(substr($item->instructor_name, 0, 1)) }}</span>
            <span class="queue-main"><strong>{{ $item->title }}</strong><small>{{ $item->instructor_name }} · {{ $item->course_name }}</small></span>
            <span class="queue-date">{{ $item->created_at ? Carbon::parse($item->created_at)->diffForHumans() : '—' }}</span>
            <span class="queue-go" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
          </a>
        @endforeach
        @if($hiddenCount > 0)
          <a class="queue-more" href="{{ route('management.requests', ['status' => 'pending']) }}">+ {{ $hiddenCount }} more {{ Str::plural('request', $hiddenCount) }} waiting</a>
        @endif
      </div>
    @else
      <div class="empty-state compact">
        <span class="empty-icon tone-green"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.500 4.500 4.500L19 7.500"/></svg></span>
        <h3>All caught up</h3>
        <p>New submissions from instructors will appear here.</p>
      </div>
    @endif
  </section>

  <aside class="dash-side">
    <section class="panel glass-card approval-panel reveal" style="--i:2" aria-labelledby="approval-title">
      <header class="panel-head tight">
        <div><span class="kicker">Course approval</span><h2 id="approval-title">{{ number_format($submitted) }} {{ Str::plural('course', $submitted) }} submitted</h2></div>
      </header>
      <div class="pipeline" role="img" aria-label="Course requests by status">
        <div class="pipeline-bar">
          @foreach(['approved' => 'green', 'pending' => 'amber', 'changes_requested' => 'yellow', 'rejected' => 'red'] as $key => $tone)
            @if($counts[$key] > 0)<span class="seg seg-{{ $tone }}" style="--w:{{ $pct($counts[$key]) }}%"></span>@endif
          @endforeach
          @if($submitted === 0)<span class="seg seg-empty" style="--w:100%"></span>@endif
        </div>
      </div>
      <div class="outcome-list">
        @foreach($outcomes as $o)
          <a class="outcome tone-{{ $o['tone'] }}" href="{{ route('management.requests', ['status' => $o['key']]) }}">
            <span class="outcome-dot"></span>
            <span class="outcome-label">{{ $o['label'] }}</span>
            <strong data-count="{{ $counts[$o['key']] }}">{{ number_format($counts[$o['key']]) }}</strong>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
          </a>
        @endforeach
      </div>
    </section>

    <section class="panel glass-card people-panel reveal" style="--i:3" aria-label="People on the platform">
      <div class="people-item">
        <span class="people-icon tone-blue"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m2 9 10-5 10 5-10 5z"/><path d="M6 11.5V16c0 1.4 2.7 3 6 3s6-1.600 6-3v-4.500M22 9v5"/></svg></span>
        <span><small>Students</small><strong data-count="{{ $people['students'] }}">{{ number_format($people['students']) }}</strong></span>
      </div>
      <div class="people-item">
        <span class="people-icon tone-violet"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4M7 9h5M7 12h8"/></svg></span>
        <span><small>Instructors</small><strong data-count="{{ $people['instructors'] }}">{{ number_format($people['instructors']) }}</strong></span>
      </div>
    </section>
  </aside>
</div>
@endsection
