{{--
  One notification row. Used by the header popover, the inbox page and previews.
  Props: $n (UserNotification), $tag ('a' or 'button', default 'a'), $href (when tag is 'a').
--}}
@php
  $a = $n->announcement;
  $official = ! $a || $a->isOfficial();
  $priority = $a->priority ?? 'normal';
  $tag = $tag ?? 'a';
  $sender = $official ? 'ZU Administration' : ($a->author->name ?? 'Instructor');
  $course = ! $official ? ($a->offering->title ?? null) : null;
  $expired = $n->isExpired();
  $unreadShown = ! $n->is_read && ! $expired;
@endphp
<{{ $tag }}
  @if($tag === 'a') href="{{ $href }}" @else type="button" data-zn-open="{{ $n->notification_id }}" @endif
  class="zn-item {{ $official ? 'src-official' : 'src-instructor' }} pri-{{ $priority }} {{ $unreadShown ? 'is-unread' : '' }} {{ $expired ? 'is-expired' : '' }}"
  data-zn-id="{{ $n->notification_id }}" @if($n->isAlerting()) data-zn-alert @endif>
  <span class="zn-avatar" aria-hidden="true">
    @if($official)
      <svg viewBox="0 0 24 24"><path d="M12 3 4.5 6v5.5c0 4.5 3.1 8.2 7.5 9.5 4.4-1.3 7.5-5 7.5-9.5V6z"/>@if($priority === 'normal')<path d="m9 12 2.2 2.2L15.5 10"/>@else<path d="M12 8.5v4M12 15.600v.4"/>@endif</svg>
    @else
      {{ strtoupper(mb_substr($sender, 0, 1)) }}
    @endif
  </span>
  <span class="zn-body">
    <span class="zn-meta">
      <span class="zn-sender">{{ $sender }}</span>
      <span class="zn-chip {{ $official ? 'zn-chip--official' : 'zn-chip--instructor' }}">{{ $official ? 'Official' : 'Instructor' }}</span>
      @if($expired)<span class="zn-chip zn-chip--expired">Expired</span>@elseif($priority !== 'normal')<span class="zn-chip zn-chip--{{ $priority }}">@if($priority === 'urgent')<i class="zn-pulse"></i>@endif{{ ucfirst($priority) }}</span>@endif
    </span>
    <strong class="zn-title">{{ $n->title() }}</strong>
    @if($a)<span class="zn-excerpt">{{ \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', $a->body), 110) }}</span>@endif
    <span class="zn-foot">
      <time datetime="{{ $n->created_at?->toIso8601String() }}" title="{{ $n->created_at?->format('M d, Y · H:i') }}">{{ $n->created_at?->diffForHumans() }}</time>
      @if($course)<span class="zn-course">{{ $course }}</span>@endif
    </span>
  </span>
  <span class="zn-state" aria-label="{{ $unreadShown ? 'Unread' : 'Read' }}"><i class="zn-dot"></i></span>
</{{ $tag }}>
