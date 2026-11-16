@extends('management.layout')
@section('title', 'Course Requests')
@section('breadcrumb', 'Course requests')
@section('content')
<div class="page-heading"><div><div class="eyebrow">COURSE APPROVALS</div><h1>Course requests</h1><p>Review submitted course information and manage approval decisions.</p></div><div class="heading-count">{{ $requests->count() }} <span>results</span></div></div>
<div class="content-card requests-card">
  <div class="tab-row" aria-label="Filter requests by approval status">
    @foreach(['pending'=>'Pending','changes_requested'=>'Needs changes','approved'=>'Approved','rejected'=>'Rejected','all'=>'All requests'] as $key=>$label)
      <a class="status-tab {{ $status===$key ? 'selected' : '' }}" href="{{ route('management.requests',['status'=>$key]) }}" {{ $status===$key ? 'aria-current=page' : '' }}>{{ $label }}</a>
    @endforeach
  </div>
  <div class="table-scroll"><table class="requests-table"><thead><tr><th>Course</th><th>Instructor</th><th>Status</th><th>Submitted</th><th class="action-heading">Action</th></tr></thead><tbody>
  @forelse($requests as $req)
    <tr><td><div class="course-cell"><span class="course-symbol">▤</span><div><strong>{{ $req->title }}</strong><small>{{ $req->college_name }}</small></div></div></td><td><div class="instructor-cell"><span class="mini-avatar">{{ strtoupper(substr($req->instructor_name,0,1)) }}</span>{{ $req->instructor_name }}</div></td><td><span class="status-badge status-{{ $req->approval_status }}"><span class="status-dot"></span>{{ ['pending'=>'Pending','changes_requested'=>'Needs changes','approved'=>'Approved','rejected'=>'Rejected'][$req->approval_status] ?? ucfirst(str_replace('_',' ',$req->approval_status)) }}</span></td><td class="date-cell">{{ $req->created_at ? \Carbon\Carbon::parse($req->created_at)->format('M d, Y') : '—' }}</td><td class="action-cell"><a class="btn btn-outline btn-sm" href="{{ route('management.requests.show',$req->offering_id) }}">{{ $req->approval_status === 'pending' ? 'Review' : 'View details' }} <span>→</span></a></td></tr>
  @empty
    <tr><td colspan="5"><div class="empty-state"><div class="empty-icon">✓</div><h3>No requests here</h3><p>There are no course requests matching this status.</p><a href="{{ route('management.requests',['status'=>'all']) }}">View all requests →</a></div></td></tr>
  @endforelse
  </tbody></table></div>
</div>
<div class="help-line"><span>ⓘ</span> Admin reviews course information only. Instructors manage their own lessons and materials.</div>
@endsection
