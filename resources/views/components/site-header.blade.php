{{-- Shared glass header. Styles: css/zu-glass.css, zu-header.css, zu-notifications.css, zu-alerts.css. Behaviour: js/zu-glass.js, zu-header.js, zu-notifications.js, zu-alerts.js --}}
<header class="zu-header" id="zuHeader">
  <div class="zu-header__start">
    @auth
      <details class="zu-account">
        <summary class="zu-btn lg zu-account__trigger" aria-label="Account menu">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c.3-5 3.1-7 8-7s7.7 2 8 7"/></svg>
          <span class="zu-account__text"><strong>{{ $user()->name }}</strong><small data-role>{{ $roleLabel() }}</small></span>
          <svg class="zu-account__chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
        </summary>
        <div class="zu-account__menu lg">
          <div class="zu-account__identity"><strong>{{ $user()->name }}</strong><span>{{ $roleLabel() }}</span></div>
          <form method="POST" action="{{ route('logout') }}">@csrf
            <button type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5H5v14h4M14 8l4 4-4 4M18 12H9"/></svg>Log out</button>
          </form>
        </div>
      </details>
      {{-- Administrators manage announcements from their own page and have no inbox, so no bell. --}}
      @if($user()->role !== 'manager')<x-notification-bell />@endif
      @if($language)<button type="button" class="zu-btn lg" id="languageButton">العربية</button>@endif
    @else
      <a class="zu-btn lg" href="{{ route('login') }}">Log in</a>
    @endauth
  </div>

  <nav class="zu-nav lg" data-zu-nav aria-label="Main navigation">
    <a class="zu-nav-link zu-nav-link--icon {{ request()->routeIs('home') ? 'is-active' : '' }}" href="{{ route('home') }}" aria-label="Home" title="Home" @if(request()->routeIs('home')) aria-current="page" @endif>
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/></svg>
    </a>
    @foreach($links() as $link)
      <a class="zu-nav-link {{ $link['active'] ? 'is-active' : '' }}" href="{{ $link['url'] }}" @if($link['active']) aria-current="page" @endif>
        {{ $link['label'] }}
        @if($link['badge'])<b class="zu-nav-badge" title="{{ $link['badge'] }} awaiting review">{{ $link['badge'] }}</b>@endif
      </a>
    @endforeach
  </nav>

  <a class="zu-brand" href="{{ route('home') }}" aria-label="Zarqa University — ZU Hub home">
    <img src="{{ asset('assets/zarqa-en-edge.png') }}" alt="Zarqa University" width="300" height="100">
  </a>
</header>

<x-announcement-banner />
