{{-- Full notification. Props: $n (UserNotification). Body is escaped, line breaks are kept. --}}
@php
  $a = $n->announcement;
  $official = ! $a || $a->isOfficial();
  $priority = $a->priority ?? 'normal';
  $sender = $official ? 'ZU Administration' : ($a->author->name ?? 'Instructor');
  $expired = $n->isExpired();
  $to = $a ? ($official ? $a->audienceLabel() : ($a->audience === 'course' ? ($a->offering->title ?? 'Your course') : 'Your students')) : null;
@endphp
<article class="zn-detail {{ $official ? 'src-official' : 'src-instructor' }} pri-{{ $priority }}">
  <header class="zn-detail__head">
    <span class="zn-avatar" aria-hidden="true">@include('notifications.partials.icon', ['official' => $official, 'priority' => $priority, 'sender' => $sender])</span>
    <span class="zn-detail__who">
      <strong>{{ $sender }}</strong>
      <small>
        <span class="zn-chip {{ $official ? 'zn-chip--official' : 'zn-chip--instructor' }}">{{ $official ? 'Official announcement' : 'Instructor' }}</span>
        @if($priority !== 'normal')<span class="zn-chip zn-chip--{{ $priority }}">{{ ucfirst($priority) }}</span>@endif
      </small>
    </span>
  </header>
  <h3 class="zn-detail__title">{{ $n->title() }}</h3>
  @if($a)<div class="zn-detail__text">{!! nl2br(e($a->body)) !!}</div>@endif
  <footer class="zn-detail__foot">
    <time datetime="{{ $n->created_at?->toIso8601String() }}">{{ $n->created_at?->format('M d, Y · H:i') }}</time>
    @if($expired)<span class="zn-expired-note">This announcement ended {{ $a->expires_at->diffForHumans() }}</span>@elseif($to)<span>To: {{ $to }}</span>@endif
  </footer>
</article>
