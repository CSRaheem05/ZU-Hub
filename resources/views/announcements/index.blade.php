@extends('layouts.glass')
@section('title', 'Announcements')
@section('content')
@php
  use Illuminate\Support\Str;
  $isAdmin = auth()->user()->role === 'manager';
  $tabs = ['all' => 'All'] + \App\Models\Announcement::STATES;
  // A time the browser re-renders in the visitor's own timezone (server fallback is UTC).
  $when = fn ($t) => $t ? '<time data-local datetime="'.$t->toIso8601String().'">'.e($t->format('M d, Y · H:i')).' UTC</time>' : '';
@endphp

<section class="page-hero reveal" style="--i:0">
  <div>
    <span class="eyebrow">{{ $isAdmin ? 'Official communication' : 'Instructor workspace' }}</span>
    <h1>Announcements</h1>
    <p>{{ $isAdmin ? 'Write to students or instructors, and choose when it starts and ends.' : 'Message the students enrolled in your own courses.' }}</p>
  </div>
  <a href="{{ route('announcements.create') }}" class="btn btn-blue btn-lg"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>New announcement</a>
</section>

<section class="panel glass-card reveal" style="--i:1">
  <div class="zu-nav zu-nav--tabs lg" data-zu-nav aria-label="Filter announcements">
    @foreach($tabs as $key => $label)
      <a class="zu-nav-link {{ $filter === $key ? 'is-active' : '' }}" href="{{ route('announcements.index', ['status' => $key]) }}" @if($filter === $key) aria-current="page" @endif><span>{{ $label }}</span><b class="count">{{ $counts[$key] }}</b></a>
    @endforeach
  </div>

  @if($items->isEmpty())
    <div class="empty-state">
      <span class="empty-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11v3a1 1 0 0 0 1 1h2l5 4V6L6 10H4a1 1 0 0 0-1 1zM15 9a4 4 0 0 1 0 6M18 6.500a8 8 0 0 1 0 11"/></svg></span>
      <h3>{{ $filter === 'all' ? 'No announcements yet' : 'Nothing '.strtolower($tabs[$filter]).' right now' }}</h3>
      <p>{{ $isAdmin ? 'Create an announcement to reach students and instructors.' : 'Create an announcement to reach the students in your courses.' }}</p>
      <a class="btn btn-blue" href="{{ route('announcements.create') }}">Create announcement</a>
    </div>
  @else
    <div class="ann-list">
      @foreach($items as $a)
        @php
          $official = $a->isOfficial();
          $mine = $a->author_id === auth()->user()->user_id;
          $state = $a->state();
          $readPct = $a->recipients_count > 0 ? round($a->read_count / $a->recipients_count * 100) : 0;
        @endphp
        <article class="ann-card {{ $official ? 'src-official' : 'src-instructor' }} pri-{{ $a->priority }} is-{{ $state }}" style="--i:{{ min($loop->index, 10) }}">
          <div class="ann-main">
            <div class="ann-chips">
              <span class="zn-chip {{ $official ? 'zn-chip--official' : 'zn-chip--instructor' }}">{{ $official ? 'Official' : 'Instructor' }}</span>
              @if($a->priority !== 'normal')<span class="zn-chip zn-chip--{{ $a->priority }}">@if($a->priority === 'urgent')<i class="zn-pulse"></i>@endif{{ ucfirst($a->priority) }}</span>@endif
              <span class="ann-status is-{{ $state }}">{{ \App\Models\Announcement::STATES[$state] }}</span>
            </div>
            <h2>{{ $a->title }}</h2>
            <p>{{ Str::limit(preg_replace('/\s+/', ' ', $a->body), 170) }}</p>
            <div class="ann-meta">
              <span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3.500"/><path d="M2.500 20c.4-3.600 2.800-5.500 6.500-5.500s6.100 1.900 6.500 5.500M16 5a3.500 3.500 0 0 1 0 6.500M18 14.800c1.900.7 3.100 2.400 3.500 5.200"/></svg>{{ $a->audienceLabel() }}</span>
              @if(! $mine)<span>By {{ $a->author->name ?? 'Unknown' }}</span>@endif
              <span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.500"/><path d="M12 7.500V12l3 2"/></svg>
                @if($state === 'draft')
                  @if($a->publish_at) Planned for {!! $when($a->publish_at) !!} @else Saved {{ $a->created_at?->diffForHumans() }} @endif
                @elseif($state === 'scheduled') Goes live {!! $when($a->goesLiveAt()) !!}
                @elseif($state === 'expired') Ended {!! $when($a->expires_at) !!}
                @else Live since {!! $when($a->goesLiveAt()) !!}
                @endif
              </span>
              @if($a->isPublished() && $state !== 'expired')<span>{{ $a->expires_at ? 'Ends' : 'No expiry' }} {!! $when($a->expires_at) !!}</span>@endif
            </div>
            @if($a->isPublished())
              <div class="ann-reach" aria-label="{{ $a->read_count }} of {{ $a->recipients_count }} read">
                <div class="ann-reach__bar"><i style="width:{{ $readPct }}%"></i></div>
                <span>{{ $state === 'scheduled' ? $a->recipients_count.' '.Str::plural('recipient', $a->recipients_count).' will receive it' : $a->recipients_count.' '.Str::plural('recipient', $a->recipients_count).' · '.$a->read_count.' read' }}</span>
              </div>
            @endif
          </div>

          <div class="ann-actions">
            @can('update', $a)
              <a class="btn btn-yellow btn-sm" href="{{ route('announcements.edit', $a) }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4L19 9a2.800 2.800 0 0 0-4-4L4 16z"/><path d="m13.500 6.500 4 4"/></svg>Edit</a>
            @endcan
            @can('publish', $a)
              <form method="POST" action="{{ route('announcements.publish', $a) }}">@csrf
                <button type="submit" class="btn btn-green btn-sm" data-zu-confirm data-confirm-tone="green" data-confirm-icon="check"
                  data-confirm-title="Publish this announcement?" data-confirm-message="{{ $a->publish_at?->isFuture() ? 'It will reach: '.$a->audienceLabel().' when it starts.' : 'It will be delivered right away to: '.$a->audienceLabel().'.' }}"
                  data-confirm-label="Publish"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 3 10 14M21 3l-7 18-4-7-7-4z"/></svg>Publish</button>
              </form>
            @endcan
            @can('delete', $a)
              <form method="POST" action="{{ route('announcements.destroy', $a) }}">@csrf @method('DELETE')
                <button type="submit" class="btn btn-red btn-sm" data-zu-confirm data-confirm-tone="red" data-confirm-icon="warning"
                  data-confirm-title="Delete this announcement?" data-confirm-message="{{ $a->isPublished() ? 'It will also disappear from every recipient\'s notifications.' : 'This draft will be removed.' }} This cannot be undone."
                  data-confirm-label="Yes, delete"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-12M9 7V4h6v3"/></svg>Delete</button>
              </form>
            @endcan
          </div>
        </article>
      @endforeach
    </div>
  @endif
</section>
@endsection
