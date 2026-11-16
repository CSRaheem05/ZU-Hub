@extends('management.layout')
@section('title', 'Dashboard')
@section('breadcrumb', 'Overview')
@section('content')
  <div class="page-heading"><div><div class="eyebrow">WELCOME TO ZU-HUB</div><h1>Your learning universe.</h1><p>A brighter way to manage courses, people, and every new learning opportunity.</p></div><a href="{{ route('management.requests') }}" class="btn btn-primary">Review requests <span>↗</span></a></div>
  <div class="stats-grid">
    <article class="stat-card"><div class="stat-upper"><span>Total users</span><span class="stat-icon tint-blue">♙</span></div><strong>{{ number_format($userCount) }}</strong><p>Registered platform accounts</p></article>
    <article class="stat-card"><div class="stat-upper"><span>Course offerings</span><span class="stat-icon tint-violet">▤</span></div><strong>{{ number_format($courseCount) }}</strong><p>All courses in the catalog</p></article>
    <article class="stat-card"><div class="stat-upper"><span>Pending review</span><span class="stat-icon tint-amber">◷</span></div><strong>{{ number_format(\Illuminate\Support\Facades\DB::table('course_offerings')->where('approval_status','pending')->count()) }}</strong><p>Requests requiring attention</p></article>
    <article class="stat-card"><div class="stat-upper"><span>Approved</span><span class="stat-icon tint-green">✓</span></div><strong>{{ number_format(\Illuminate\Support\Facades\DB::table('course_offerings')->where('approval_status','approved')->count()) }}</strong><p>Courses approved for publishing</p></article>
  </div>
  <div class="overview-grid"><section class="content-card action-card"><div class="section-kicker">WORKFLOW</div><h2>Course approval center</h2><p>Review new course details, request corrections from instructors, and approve courses for the student catalog. Lesson and material management remain with instructors.</p><a href="{{ route('management.requests') }}" class="btn btn-primary">Open course requests <span>→</span></a></section><section class="content-card"><div class="section-kicker">HOW IT WORKS</div><h2>From request to classroom</h2><div class="process-list"><div><span>01</span><p><strong>Instructor submits</strong><small>Course details are sent for approval.</small></p></div><div><span>02</span><p><strong>Admin reviews</strong><small>Approve, request changes, or reject.</small></p></div><div><span>03</span><p><strong>Course becomes available</strong><small>Approved and active courses appear to students.</small></p></div></div></section></div>
@endsection
