{{--
  One notification row. Used by the header popover, the inbox page and previews.
  Props: $n (UserNotification), $tag ('a' or 'button', default 'a'), $href (when tag is 'a'),
         $mark (true in the bell panels: adds a "mark as read" control to unread rows).
--}}
@php
  $a = $n->announcement;
  $official = ! $a || $a->isOfficial();
  $priority = $a->priority ?? 'normal';
  $tag = $tag ?? 'a';
  $mark = $mark ?? false;
  $sender = $official ? 'ZU Administration' : ($a->author->name ?? 'Instructor');
  $course = ! $official ? ($a->offering->title ?? null) : null;
  $expired = $n->isExpired();
  $unreadShown = ! $n->is_read && ! $expired;
@endphp
<article class="zn-item {{ $official ? 'src-official' : 'src-instructor' }} pri-{{ $priority }} {{ $unreadShown ? 'is-unread' : '' }} {{ $expired ? 'is-expired' : '' }}"
  data-zn-id="{{ $n->notification_id }}" @if($n->isAlerting()) data-zn-alert @endif>
  <{{ $tag }} class="zn-main"
    @if($tag === 'a') href="{{ $href }}" @else type="button" data-zn-open="{{ $n->notification_id }}" @endif>
    <span class="zn-avatar" aria-hidden="true">@include('notifications.partials.icon', ['official' => $official, 'priority' => $priority, 'sender' => $sender])</span>
    <span class="zn-body">
      <span class="zn-meta">
        @if($expired)<span class="zn-chip zn-chip--expired">Expired</span>@elseif($priority !== 'normal')<span class="zn-chip zn-chip--{{ $priority }}">{{ ucfirst($priority) }}</span>@endif
        <span class="zn-sender">{{ $sender }}</span>
      </span>
      <strong class="zn-title" dir="auto">{{ $n->title() }}</strong>
      @if($a)<span class="zn-excerpt" dir="auto">{{ \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', $a->body), 110) }}</span>@endif
      <span class="zn-foot">
        <time dir="ltr" datetime="{{ $n->created_at?->toIso8601String() }}" title="{{ $n->created_at?->format('M d, Y · H:i') }}">{{ $n->created_at?->diffForHumans() }}</time>
        @if($course)<span class="zn-course">{{ $course }}</span>@endif
      </span>
    </span>
  </{{ $tag }}>
  <span class="zn-state">
    @if($mark && $unreadShown)<button type="button" class="zn-mark" data-zn-mark="{{ $n->notification_id }}" aria-label="Mark as read" title="Mark as read"><i class="zn-dot"></i></button>
    @else<i class="zn-dot" @if($unreadShown) role="img" aria-label="Unread" @endif></i>@endif
  </span>
</article>
