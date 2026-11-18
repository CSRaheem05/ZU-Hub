@extends('layouts.glass')
@section('title', 'Notifications')
@section('content')
<section class="page-hero reveal" style="--i:0">
  <div>
    <span class="eyebrow">Inbox</span>
    <h1>Notifications</h1>
    <p>Announcements from the university and your instructors, kept here whenever you need them.</p>
  </div>
  @if($unread > 0)
    <form method="POST" action="{{ route('notifications.read-all') }}" data-submit-once>@csrf
      <button type="submit" class="btn btn-glass"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 12.5 4.500 4.500L16 8.500M12 16.500l1.500 1.500L21 9.500"/></svg>Mark all as read</button>
    </form>
  @endif
</section>

<section class="panel glass-card reveal" style="--i:1">
  <div class="inbox-toolbar">
    <div class="zu-nav zu-nav--tabs lg" data-zu-nav style="margin:0" aria-label="Filter notifications">
      <a class="zu-nav-link {{ $filter === 'all' ? 'is-active' : '' }}" href="{{ route('notifications.index') }}" @if($filter === 'all') aria-current="page" @endif><span>All</span><b class="count">{{ $total }}</b></a>
      <a class="zu-nav-link {{ $filter === 'unread' ? 'is-active' : '' }}" href="{{ route('notifications.index', ['filter' => 'unread']) }}" @if($filter === 'unread') aria-current="page" @endif><span>Unread</span><b class="count">{{ $unread }}</b></a>
    </div>
  </div>

  @if($notifications->isEmpty())
    <div class="empty-state">
      <span class="empty-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg></span>
      <h3>{{ $filter === 'unread' ? 'Nothing unread' : 'No notifications yet' }}</h3>
      <p>{{ $filter === 'unread' ? 'You have read everything. Nice work.' : 'When someone sends you an announcement, it will show up here.' }}</p>
    </div>
  @else
    <div class="inbox-list">
      @foreach($notifications as $n)
        <div style="--i:{{ $loop->index }}">@include('notifications.partials.card', ['n' => $n, 'tag' => 'a', 'href' => route('notifications.show', $n->notification_id)])</div>
      @endforeach
    </div>

    @if($notifications->hasPages())
      <nav class="pager" aria-label="Pagination">
        @if($notifications->onFirstPage())<span class="disabled">← Newer</span>@else<a href="{{ $notifications->previousPageUrl() }}">← Newer</a>@endif
        <span>Page {{ $notifications->currentPage() }} of {{ $notifications->lastPage() }}</span>
        @if($notifications->hasMorePages())<a href="{{ $notifications->nextPageUrl() }}">Older →</a>@else<span class="disabled">Older →</span>@endif
      </nav>
    @endif
  @endif
</section>
@endsection
