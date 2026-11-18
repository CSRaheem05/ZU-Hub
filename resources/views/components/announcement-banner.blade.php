{{-- Official announcement banner (Important / Urgent). One at a time; dismissing reveals the next.
     Styles: css/zu-alerts.css (+ tokens in zu-notifications.css) · Behaviour: js/zu-alerts.js --}}
<section class="zu-alerts" data-zu-alerts
         data-token="{{ csrf_token() }}"
         data-seen-url="{{ route('notifications.alerts-seen') }}"
         data-ids='@json($ids)'
         data-more="{{ $more }}"
         data-inbox="{{ route('notifications.index') }}"
         aria-label="Official announcements">
  @foreach($items as $n)
    @php $a = $n->announcement; $urgent = $a->priority === 'urgent'; @endphp
    <article class="zu-alert pri-{{ $a->priority }}" role="{{ $urgent ? 'alert' : 'status' }}" @unless($urgent) data-autohide="20000" @endunless>
      <span class="zu-alert__icon" aria-hidden="true">
        @if($urgent)<svg viewBox="0 0 24 24"><path d="M10.3 4.2 2.9 17.5A2 2 0 0 0 4.6 20.500h14.800a2 2 0 0 0 1.700-3L13.700 4.200a2 2 0 0 0-3.400 0z"/><path d="M12 9.500v4M12 16.700v.1"/></svg>
        @else<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7.500v5M12 15.800v.1"/></svg>@endif
      </span>
      <div class="zu-alert__body">
        <p class="zu-alert__eyebrow"><span class="zu-alert__tag">{{ $urgent ? 'Urgent' : 'Important' }}</span><span>ZU Administration</span><a class="zu-alert__more" data-zu-more href="{{ route('notifications.index') }}" hidden></a></p>
        <strong class="zu-alert__title" dir="auto">{{ $a->title }}</strong>
        <p class="zu-alert__text" dir="auto">{{ \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', $a->body), 160) }}</p>
      </div>
      <div class="zu-alert__actions">
        <a class="zu-alert__read" href="{{ route('notifications.show', $n->notification_id) }}">Read<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
        <button type="button" class="zu-alert__close" aria-label="Dismiss announcement"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
      </div>
    </article>
  @endforeach
</section>
