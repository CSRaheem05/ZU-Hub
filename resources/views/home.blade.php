<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#f8fbff">
  <title>ZU Hub | Learn Beyond the Classroom</title>
  <meta name="description" content="ZU Hub — a creative home for university learning.">
 

 <link rel="stylesheet" href="{{ asset('css/home.css') }}">
  <link rel="stylesheet" href="{{ asset('css/home-extra.css') }}">
 
</head>

<body data-zu-guest="@guest 1 @else 0 @endguest" data-login-url="{{ route('login') }}">
  <main class="page" id="page">
    <div class="paper" id="paper" aria-hidden="true"></div>
    <svg class="waves" id="waves" viewBox="0 0 1440 960" preserveAspectRatio="none" aria-hidden="true">
      <g stroke-width="2.2" opacity=".77">
        <path d="M -260 1040 C 40 800 85 510 460 375 S 1010 152 1690 -110" />
        <path d="M -220 1066 C 90 826 130 544 490 403 S 1030 180 1720 -65" stroke-width="3.8" />
        <path d="M -185 1090 C 112 858 170 571 520 435 S 1060 218 1740 -30" />
        <path d="M -160 1120 C 145 890 210 604 562 472 S 1110 254 1760 18" stroke-width="1.3" />
        <path d="M -138 1150 C 175 927 246 648 601 511 S 1150 298 1770 62" stroke-width="3.1" />
        <path d="M -110 1180 C 204 955 285 686 638 552 S 1190 340 1800 105" />
        <path d="M -80 1210 C 235 984 317 727 674 588 S 1230 380 1840 145" stroke-width="1.5" />
        <path d="M -52 1240 C 265 1014 345 760 712 633 S 1270 420 1850 195" stroke-width="3.7" />
        <path d="M -20 1270 C 300 1043 380 802 751 670 S 1320 466 1880 230" />
        <path d="M 10 1300 C 332 1074 420 850 790 711 S 1370 506 1900 275" stroke-width="2.9" />
        <path d="M 42 1330 C 364 1109 455 885 828 748 S 1410 540 1920 318" />
        <path d="M 74 1366 C 398 1142 490 925 867 786 S 1460 584 1940 360" stroke-width="3.8" />
        <path d="M 106 1396 C 430 1173 525 968 905 822 S 1490 621 1950 395" stroke-width="1.4" />
        <path d="M 138 1432 C 462 1206 570 1003 946 859 S 1540 662 1980 435" />
        <path d="M 170 1465 C 495 1235 602 1040 982 899 S 1590 710 2010 484" stroke-width="3.6" />
      </g>
    </svg>
    <header class="nav" id="nav">
      <div class="header-left" id="headerLeft">
        <div class="campus-brand brand-ar" aria-label="شعار جامعة الزرقاء بالعربية">
          <img src="{{ asset('assets/zarqa-ar-edge.png') }}" alt="شعار جامعة الزرقاء" width="300" height="100">
        </div>
        <div class="header-tools" id="headerTools">
          @guest
          <a class="glass-btn login-link" id="loginLink" href="{{ route('login') }}" data-login-link aria-label="Log in">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3M13 3h5a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3h-5" /></svg>
            <span data-login-label>Log in</span>
          </a>
          @else
          <details class="zu-account" id="zuAccountMenu">
            <summary class="glass-btn zu-account-trigger" aria-label="Account menu">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c.3-5 3.1-7 8-7s7.7 2 8 7"/></svg>
              <span class="zu-account-text"><strong>{{ auth()->user()->name }}</strong><small data-role="{{ auth()->user()->role }}">{{ ['student'=>'Student','instructor'=>'Instructor','manager'=>'Management'][auth()->user()->role] ?? 'User' }}</small></span>
              <span aria-hidden="true">⌄</span>
            </summary>
            <div class="zu-account-dropdown">
              <div class="zu-menu-identity">{{ auth()->user()->name }} <span data-role="{{ auth()->user()->role }}">{{ ['student'=>'Student','instructor'=>'Instructor','manager'=>'Management'][auth()->user()->role] ?? 'User' }}</span></div>
              <form method="POST" action="{{ route('logout') }}">@csrf
                <button type="submit" data-nav-label="logout">Log out</button>
              </form>
            </div>
          </details>
          @endguest
          <div class="nav-actions">
            <button class="glass-btn" type="button" id="notifBtn" @guest style="display:none" @endguest aria-label="Notifications" aria-haspopup="dialog"
              aria-expanded="false" title="Notifications">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" />
              </svg>
              <span class="notif-badge" id="notifDot"></span>
            </button>
            <button class="glass-btn lang-btn" type="button" id="langBtn" aria-label="Switch language"
              title="Switch language">
              <svg class="language-globe" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="9" />
                <path d="M3 12h18M12 3c3 3 4 6 4 9s-1 6-4 9M12 3c-3 3-4 6-4 9s1 6 4 9" />
              </svg>
              <span class="mini-label" id="langLabel">العربية</span>
            </button>
          </div>
        </div>
      </div>
      <nav class="nav-links" aria-label="Main navigation" id="mainNav">
        <button class="nav-link active zu-home-link" type="button" data-view="home" aria-label="Home" title="Home">
          <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/></svg>
        </button>
        @guest
          <a class="nav-link zu-nav-anchor" href="{{ route('login') }}" data-nav-label="courses">Courses</a>
        @else
          <a class="nav-link zu-nav-anchor" href="{{ route('courses.index') }}" data-nav-label="courses">Courses</a>
          @if(auth()->user()->role === 'student')
            <a class="nav-link zu-nav-anchor" href="{{ route('student.courses') }}" data-nav-label="myCourses">My Courses</a>
          @elseif(auth()->user()->role === 'instructor')
            <a class="nav-link zu-nav-anchor" href="{{ route('instructor.courses') }}" data-nav-label="myCourses">My Courses</a>
          @elseif(auth()->user()->role === 'manager')
            <a class="nav-link zu-nav-anchor" href="{{ route('management.index') }}" data-nav-label="management">Management</a>
          @endif
        @endguest
      </nav>
      <div class="header-right" id="headerRight">
        <div class="campus-brand brand-en" aria-label="Zarqa University English logo">
          <img src="{{ asset('assets/zarqa-en-edge.png') }}" alt="Zarqa University" width="300" height="100">
        </div>
      </div>
    </header>
    <aside class="popover-panel" id="notifPanel" role="dialog" aria-label="Notifications" hidden>
      <div class="panel-head"><span class="panel-title" data-i18n="notifications">Notifications</span><span
          class="panel-count" data-i18n="demoNotices">Demo notifications</span></div>
      <div id="noticeList"></div>
      <button class="panel-action" type="button" id="markRead" data-i18n="markRead">Mark all as read</button>
    </aside>
    <section class="hero" id="home" aria-label="ZU Hub introduction">
      <div class="wordmark" id="wordmark">
        <span class="half left">ZU</span>
        <div class="pill" id="pill" role="group" aria-label="Scenic video in the centre of ZU Hub">
          <div class="pill-media">
            <div class="fallback"></div>
            <img class="poster" id="poster" alt="Floating island at sunset"
              src="https://d2ol7oe51mr4n9.cloudfront.net/user_38xzZboKViGWJOttwIXH07lWA1P/726b168c-da47-4682-a255-cc3e02f89bae.jpg">

            <video id="heroVideo" autoplay muted loop playsinline preload="auto" aria-label="ZU Hub Robot Video">
              <source src="{{ asset('videos/Robot.mp4') }}" type="video/mp4">
            </video>

          </div>
          <div class="hairline" id="hairline" aria-hidden="true"></div>
        </div>
        <span class="half right">Hub</span>
      </div>
      <p class="microcopy" id="microcopy" data-i18n="tagline">Learn beyond the classroom</p>
    </section>

    <footer class="footer" id="footer" aria-hidden="true"><span class="footer-rule" id="footerRule"></span></footer>
    <div class="footer-small" id="footerSmall">ZU HUB © 2026 · MADE FOR THE NEXT GENERATION</div>
    <div class="overlay" id="overlay" hidden>
      <section class="sheet" id="sheet" role="dialog" aria-modal="true" aria-labelledby="sheetTitle">
        <div class="sheet-top"><span class="eyebrow" id="sheetEyebrow">ZU HUB EXPERIENCE</span><button
            class="glass-btn close-btn" id="closeSheet" type="button" aria-label="Close"><svg viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2">
              <path d="M6 6l12 12M18 6 6 18" />
            </svg></button></div>
        <h2 id="sheetTitle"></h2>
        <p class="lead" id="sheetLead"></p>
        <div id="sheetContent"></div>
        <p class="sheet-foot" id="sheetNote"></p>
      </section>
    </div>
  </main>


  <!-- A compact introduction below the existing robot hero. -->
  <div class="zu-bottom-content" id="zuBottomContent">
    <section class="zu-intro" id="zuIntro" aria-labelledby="zuIntroTitle">
      <div class="zu-intro-inner">
        <span class="zu-intro-kicker" data-zu-en="WELCOME TO ZU HUB" data-zu-ar="مرحبًا بك في ZU HUB">WELCOME TO ZU HUB</span>
        <h2 id="zuIntroTitle" data-zu-en="A simpler way to learn." data-zu-ar="تجربة تعليمية أسهل.">A simpler way to learn.</h2>
        <p data-zu-en="Explore courses, follow your learning, and stay connected to your university experience — all in one place." data-zu-ar="اكتشف الدورات، وتابع تعلمك، واستمتع بتجربة جامعية متكاملة في مكان واحد.">Explore courses, follow your learning, and stay connected to your university experience — all in one place.</p>
        @guest
          <a class="zu-intro-action" href="{{ route('login') }}"><span data-zu-en="Explore courses" data-zu-ar="استكشف الدورات">Explore courses</span><span aria-hidden="true">↗</span></a>
        @else
          <a class="zu-intro-action" href="{{ route('courses.index') }}"><span data-zu-en="Explore courses" data-zu-ar="استكشف الدورات">Explore courses</span><span aria-hidden="true">↗</span></a>
        @endguest
      </div>
    </section>
    <footer class="site-footer zu-restored-footer">
      <div class="landing-shell footer-layout">
        <div class="footer-brand">ZU Hub<span style="color:#3a9de2">.</span></div>
        <p data-zu-en="Zarqa University · A space for learning · © {{ date('Y') }}" data-zu-ar="جامعة الزرقاء · مساحة للتعلّم · © {{ date('Y') }}">Zarqa University · A space for learning · © {{ date('Y') }}</p>
        <a href="#home" class="zu-backtop" data-zu-en="Back to top ↑" data-zu-ar="العودة للأعلى ↑">Back to top ↑</a>
      </div>
    </footer>
  </div>

  <script src="{{ asset('js/home.js') }}" defer></script>
</body>

</html>