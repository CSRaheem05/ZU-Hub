{{-- Slide-down alert for Important/Urgent admin announcements. Styles: css/zu-alerts.css · Behaviour: js/zu-alerts.js --}}
<section class="zu-alerts" data-zu-alerts
         data-token="{{ csrf_token() }}"
         data-seen-url="{{ route('notifications.alerts-seen') }}"
         data-ids='@json($ids)'
         aria-label="Important announcements">
  @foreach($items as $n)
    @php $a = $n->announcement; $urgent = $a->priority === 'urgent'; @endphp
    <article class="zu-alert pri-{{ $a->priority }}" style="--i:{{ $loop->index }}"
             role="{{ $urgent ? 'alert' : 'status' }}" @unless($urgent) data-autohide="16000" @endunless>
      <span class="zu-alert__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M12 3 4.5 6v5.5c0 4.5 3.1 8.2 7.5 9.5 4.4-1.3 7.5-5 7.5-9.5V6z"/><path d="M12 8.5v4M12 15.6v.4"/></svg>
      </span>
      <div class="zu-alert__body">
        <small>{{ $urgent ? 'Urgent' : 'Important' }} · ZU Administration</small>
        <strong>{{ $a->title }}</strong>
        <p>{{ \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', $a->body), 130) }}</p>
      </div>
      <div class="zu-alert__actions">
        <a class="zu-alert__read" href="{{ route('notifications.show', $n->notification_id) }}">Read</a>
        <button type="button" class="zu-alert__close" aria-label="Dismiss announcement"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
      </div>
      @unless($urgent)<i class="zu-alert__timer" aria-hidden="true"></i>@endunless
    </article>
  @endforeach
  @if($more > 0)
    <a class="zu-alerts__more" href="{{ route('notifications.index') }}">+ {{ $more }} more {{ \Illuminate\Support\Str::plural('announcement', $more) }} in your notifications</a>
  @endif
</section>
