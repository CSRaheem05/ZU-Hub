{{-- Notification bell for the shared header. Styles: css/zu-notifications.css · Behaviour: js/zu-notifications.js --}}
<div class="zu-notify {{ $alerts ? 'has-alert' : '' }}" data-zu-notify
     data-token="{{ csrf_token() }}"
     data-read-url="{{ route('notifications.read', ['notificationId' => '__ID__']) }}"
     data-read-all-url="{{ route('notifications.read-all') }}">
  <button type="button" class="zu-btn lg zu-notify__trigger" aria-haspopup="dialog" aria-expanded="false" aria-controls="zuNotifyPanel"
          aria-label="Notifications{{ $unread ? ', '.$unread.' unread' : '' }}{{ $alerts ? ', important announcement' : '' }}" title="Notifications">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
    <b class="zu-notify__badge" @if(! $unread) hidden @endif>{{ $unread > 9 ? '9+' : $unread }}</b>
  </button>

  <section class="zu-notify__panel lg" id="zuNotifyPanel" role="dialog" aria-label="Notifications" hidden>
    <span class="zu-notify__caret" aria-hidden="true"></span>
    <div class="zu-notify__view" data-view="list">
      <header class="zu-notify__head">
        <div>
          <h2>Notifications</h2>
          <small data-zn-summary>{{ $unread ? $unread.' unread' : 'You are all caught up' }}</small>
        </div>
        <button type="button" class="zu-notify__link" data-zn-read-all @if(! $unread) hidden @endif>Mark all as read</button>
      </header>

      @if($items->isEmpty())
        <div class="zu-notify__empty">
          <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg></span>
          <strong>No notifications yet</strong>
          <p>Announcements from the university and your instructors will appear here.</p>
        </div>
      @else
        <div class="zu-notify__list">
          @foreach($items as $item)
            @include('notifications.partials.card', ['n' => $item, 'tag' => 'button'])
          @endforeach
        </div>
      @endif

      <footer class="zu-notify__foot"><a href="{{ route('notifications.index') }}">View all notifications <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a></footer>
    </div>

    <div class="zu-notify__view" data-view="detail" hidden>
      <header class="zu-notify__head zu-notify__head--back">
        <button type="button" class="zu-notify__back" data-zn-back aria-label="Back to notifications"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg></button>
        <h2>Notification</h2>
      </header>
      <div class="zu-notify__details">
        @foreach($items as $item)
          <div class="zu-notify__detail" data-zn-detail="{{ $item->notification_id }}" hidden>
            @include('notifications.partials.detail', ['n' => $item])
          </div>
        @endforeach
      </div>
    </div>
  </section>
</div>
