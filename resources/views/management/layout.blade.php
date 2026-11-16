<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#e6f3ff"><title>@yield('title','Administration') | ZU-Hub</title>
<link rel="stylesheet" href="{{ asset('css/zu-admin-glass.css') }}?v=2">
</head>
<body>
<div class="ambient" aria-hidden="true"><div class="glow orb-one"></div><div class="glow orb-two"></div><svg class="wave-art" viewBox="0 0 1440 1000" preserveAspectRatio="none"><g fill="none" stroke="#fff" stroke-width="2.5" opacity=".7"><path d="M-210 1030C140 610 330 610 590 450S1120 180 1650-180"/><path d="M-180 1090C160 660 340 665 625 490S1150 230 1660-120"/><path d="M-140 1150C190 720 360 710 655 525S1200 290 1690-60"/><path d="M-105 1210C225 780 390 760 710 560S1240 345 1720 0"/><path d="M-60 1270C265 840 430 820 755 610S1280 405 1740 90"/><path d="M-20 1350C310 910 475 890 810 670S1340 470 1810 150"/><path d="M45 1430C340 975 525 965 855 735S1380 540 1880 215"/></g></svg></div>
<header class="floating-header">
 <a href="{{ route('home') }}" class="zu-logo" aria-label="ZU-Hub home"><span class="zu-logo-symbol">ZU</span><span class="zu-logo-word">Hub<small>ADMIN SPACE</small></span></a>
 <nav class="glass-navigation" aria-label="Admin navigation">
   <a href="{{ route('management.index') }}" class="glass-nav-link {{ request()->routeIs('management.index') ? 'active' : '' }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 11 9-8 9 8v9H3zM9 20v-7h6v7"/></svg><span>Overview</span></a>
   <a href="{{ route('management.requests') }}" class="glass-nav-link {{ request()->routeIs('management.requests*') ? 'active' : '' }}"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 9h6M9 13h6M9 17h4"/></svg><span>Course Requests</span></a>
   <a href="{{ route('home') }}" class="glass-nav-link"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 4h6v6M20 4l-9 9M20 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h5"/></svg><span>Website</span></a>
 </nav>
 <div class="user-actions"><div class="user-pill"><span class="avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A',0,1)) }}</span><span class="user-caption"><strong>{{ auth()->user()->name ?? 'Admin' }}</strong><small>Administrator</small></span></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="signout" type="submit" title="Log out" aria-label="Log out"><svg viewBox="0 0 24 24"><path d="M9 5H5v14h4M14 8l4 4-4 4M18 12H9"/></svg></button></form></div>
</header>
<main class="site-main">
 @if(session('success'))<div class="flash flash-success" role="status"><span>✓</span>{{ session('success') }}</div>@endif
 @if($errors->any())<div class="flash flash-error" role="alert"><strong>Please check:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
 @yield('content')
 <footer class="site-footer"><span>ZU HUB <b>© {{ date('Y') }}</b> · MADE FOR THE NEXT GENERATION</span><span>Zarqa University · Administration</span></footer>
</main>
@stack('scripts')
</body></html>
