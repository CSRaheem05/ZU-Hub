<!doctype html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#edf6ff">
    <title>ZU Hub | Sign in</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <script>
        /* apply the saved language before first paint, so an Arabic visitor never sees the page flip direction */
        try { if (localStorage.getItem('zu-language') === 'ar') { document.documentElement.lang = 'ar'; document.documentElement.dir = 'rtl'; } } catch (e) {}
    </script>
</head>
<body>
    {{-- decorative backdrop: soft aurora light, a fading dot grid and a light beam behind the card --}}
    <div class="backdrop" aria-hidden="true">
        <span class="aurora a1"></span>
        <span class="aurora a2"></span>
        <span class="aurora a3"></span>
        <span class="dot-grid"></span>
        <span class="beam"></span>
    </div>

    <svg class="waves" viewBox="0 0 1440 960" preserveAspectRatio="none" aria-hidden="true">
        <defs>
            <linearGradient id="flowGlow" x1="0" y1="1" x2="1" y2="0">
                <stop offset="0" stop-color="#ffffff" stop-opacity="0"/>
                <stop offset=".45" stop-color="#9bd3ff"/>
                <stop offset=".7" stop-color="#ffffff"/>
                <stop offset="1" stop-color="#ffffff" stop-opacity="0"/>
            </linearGradient>
        </defs>
        <g class="wave-lines" stroke-width="2.2" opacity=".8">
            <path d="M -260 1040 C 40 800 85 510 460 375 S 1010 152 1690 -110" />
            <path d="M -220 1066 C 90 826 130 544 490 403 S 1030 180 1720 -65" stroke-width="3.8" />
            <path d="M -185 1090 C 112 858 170 571 520 435 S 1060 218 1740 -30" />
            <path d="M -160 1120 C 145 890 210 604 562 472 S 1110 254 1760 18" stroke-width="1.3" />
            <path d="M -138 1150 C 175 927 246 648 601 511 S 1150 298 1770 62" stroke-width="3.1" />
            <path d="M -110 1180 C 204 955 285 686 638 552 S 1190 340 1800 105" />
            <path d="M -52 1240 C 265 1014 345 760 712 633 S 1270 420 1850 195" stroke-width="3.7" />
            <path d="M 10 1300 C 332 1074 420 850 790 711 S 1370 506 1900 275" stroke-width="2.9" />
            <path d="M 74 1366 C 398 1142 490 925 867 786 S 1460 584 1940 360" stroke-width="3.8" />
        </g>
        {{-- light travelling along three of the lines --}}
        <g class="flow-lines" stroke="url(#flowGlow)">
            <path pathLength="1" d="M -220 1066 C 90 826 130 544 490 403 S 1030 180 1720 -65" stroke-width="3.2" style="--d:0s" />
            <path pathLength="1" d="M -138 1150 C 175 927 246 648 601 511 S 1150 298 1770 62" stroke-width="2.6" style="--d:-4.5s" />
            <path pathLength="1" d="M 10 1300 C 332 1074 420 850 790 711 S 1370 506 1900 275" stroke-width="2.4" style="--d:-8s" />
        </g>
    </svg>

    <header class="topbar">
        <a class="glass-pill back-link" href="{{ route('home') }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
            <span data-i18n="back">Back to homepage</span>
        </a>
        <button class="glass-pill language-button" id="languageButton" type="button" aria-label="التبديل إلى العربية" lang="ar">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.6 2.6 3.9 5.6 3.9 9s-1.3 6.4-3.9 9c-2.6-2.6-3.9-5.6-3.9-9S9.4 5.6 12 3z"/></svg>
            <span>العربية</span>
        </button>
    </header>

    <main class="stage">
        <section class="panel" aria-labelledby="loginTitle">
            <span class="panel-fx panel-sheen" aria-hidden="true"></span>
            <span class="panel-fx panel-spot" aria-hidden="true"></span>

            <div class="logo-plate">
                <img id="universityLogo"
                     src="{{ asset('assets/zarqa-en-edge.png') }}"
                     data-en-src="{{ asset('assets/zarqa-en-edge.png') }}"
                     data-ar-src="{{ asset('assets/zarqa-ar-edge.png') }}"
                     alt="Zarqa University logo">
            </div>

            <div class="brand">
                <span class="brand-halo" aria-hidden="true"></span>
                <h1 id="loginTitle" class="wordmark" aria-label="ZU Hub"><span class="half left" data-text="ZU" aria-hidden="true">ZU</span> <span class="half right" data-text="Hub" aria-hidden="true">Hub</span></h1>
                <span class="ornament" aria-hidden="true"><i></i></span>
            </div>

            <div class="error-box" id="loginError" role="alert">{{ $errors->first() }}</div>

            <form action="{{ route('login.store') }}" method="POST" id="loginForm">
                @csrf
                <div class="field" style="--i:1">
                    <label for="email" data-i18n="email">University email</label>
                    <div class="input-wrap">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6"/></svg>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" maxlength="255"
                               autocomplete="username" inputmode="email" autocapitalize="none" spellcheck="false"
                               placeholder="name@zu.edu.jo" required>
                    </div>
                </div>

                <div class="field" style="--i:2">
                    <label for="password" data-i18n="password">Password</label>
                    <div class="input-wrap">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2.5"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                        <input type="password" name="password" id="password" autocomplete="current-password"
                               placeholder="Enter your password" aria-describedby="capsHint" required>
                        <button type="button" id="passwordToggle" class="reveal-btn" aria-controls="password" aria-pressed="false" aria-label="Show password">
                            <svg class="eye" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-off" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18M10.6 5.1A10 10 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.2 4M6.5 6.6C3.6 8.4 2 12 2 12s3.6 7 10 7c1.7 0 3.2-.4 4.5-1M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                        </button>
                    </div>
                    <p class="hint" id="capsHint" role="status" hidden></p>
                </div>

                <button type="submit" class="submit-button" id="submitButton" style="--i:3">
                    <span class="spinner" aria-hidden="true"></span>
                    <span data-i18n="signIn">Sign in</span>
                    <svg class="submit-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </button>
            </form>

            <p class="fineprint" style="--i:4">© {{ date('Y') }} ZU Hub · <span data-i18n="copyright">Zarqa University</span></p>
        </section>
    </main>

    <script src="{{ asset('js/login.js') }}"></script>
</body>
</html>
