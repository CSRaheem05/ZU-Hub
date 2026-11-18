@extends('management.layout')
@section('title', 'Course Requests')
@section('content')
@php
  $tabs = ['pending' => 'Pending', 'changes_requested' => 'Needs changes', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All requests'];
@endphp

<section class="page-hero reveal" style="--i:0">
  <div>
    <span class="eyebrow">Course approvals</span>
    <h1>Course requests</h1>
    <p>Review submitted course information and manage approval decisions.</p>
  </div>
</section>

<section class="panel glass-card reveal" style="--i:1">
  <div class="zu-nav zu-nav--tabs lg" data-zu-nav role="navigation" aria-label="Filter requests by approval status">
    @foreach($tabs as $key => $label)
      <a class="zu-nav-link {{ $status === $key ? 'is-active' : '' }}" href="{{ route('management.requests', ['status' => $key]) }}" @if($status === $key) aria-current="page" @endif>
        <span>{{ $label }}</span><b class="count">{{ $counts[$key] }}</b>
      </a>
    @endforeach
  </div>

  @if($requests->isEmpty())
    <div class="empty-state">
      <span class="empty-icon tone-green"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.500 4.500 4.500L19 7.500"/></svg></span>
      <h3>No requests here</h3>
      <p>There are no course requests matching this status.</p>
      <a class="btn btn-glass" href="{{ route('management.requests', ['status' => 'all']) }}">View all requests</a>
    </div>
  @else
    <div class="request-list" role="list">
      <div class="request-head" aria-hidden="true"><span>Course</span><span>Instructor</span><span>Status</span><span>Submitted</span><span></span></div>
      @foreach($requests as $req)
        <article class="request-row" role="listitem" style="--i:{{ min($loop->index, 12) }}">
          <div class="req-course">
            <span class="course-symbol"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5.500A1.500 1.500 0 0 1 5.500 4H20v14H5.500A1.500 1.500 0 0 0 4 19.500z"/><path d="M4 19.500A1.500 1.500 0 0 0 5.500 21H20M8 8h8"/></svg></span>
            <div><strong>{{ $req->title }}</strong><small>{{ $req->college_name }}</small></div>
          </div>
          <div class="req-instructor"><span class="mini-avatar">{{ strtoupper(substr($req->instructor_name, 0, 1)) }}</span><span>{{ $req->instructor_name }}</span></div>
          <div class="req-status">@include('management.partials.status-badge', ['status' => $req->approval_status])</div>
          <div class="req-date">{{ $req->created_at ? \Carbon\Carbon::parse($req->created_at)->format('M d, Y') : '—' }}</div>
          <div class="req-action">
            <a class="btn {{ $req->approval_status === 'pending' ? 'btn-blue' : 'btn-glass' }} btn-sm" href="{{ route('management.requests.show', $req->offering_id) }}">
              {{ $req->approval_status === 'pending' ? 'Review' : 'View details' }}
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
          </div>
        </article>
      @endforeach
    </div>
  @endif
</section>

<p class="help-line"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.5"/></svg>Admin reviews course information only. Instructors manage their own lessons and materials.</p>
@endsection
