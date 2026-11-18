<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#e6f3ff">
<title>@yield('title', 'ZU Hub') | ZU-Hub</title>
@foreach(['zu-glass', 'zu-header', 'zu-notifications', 'zu-alerts', 'zu-course-cover', 'zu-modal', 'zu-admin-glass'] as $sheet)
<link rel="stylesheet" href="{{ asset("css/{$sheet}.css") }}?v={{ @filemtime(public_path("css/{$sheet}.css")) }}">
@endforeach
@stack('styles')
</head>
<body>
<div class="ambient" aria-hidden="true">
  <div class="glow orb-one"></div><div class="glow orb-two"></div>
  <svg class="wave-art" viewBox="0 0 1440 1000" preserveAspectRatio="none"><g fill="none" stroke="#fff" stroke-width="2.5" opacity=".7"><path d="M-210 1030C140 610 330 610 590 450S1120 180 1650-180"/><path d="M-180 1090C160 660 340 665 625 490S1150 230 1660-120"/><path d="M-140 1150C190 720 360 710 655 525S1200 290 1690-60"/><path d="M-105 1210C225 780 390 760 710 560S1240 345 1720 0"/><path d="M-60 1270C265 840 430 820 755 610S1280 405 1740 90"/><path d="M-20 1350C310 910 475 890 810 670S1340 470 1810 150"/><path d="M45 1430C340 975 525 965 855 735S1380 540 1880 215"/></g></svg>
</div>

<x-site-header :language="trim($__env->yieldContent('header_language')) === '1'" />

<main class="site-main">
  @if(session('success'))<div class="flash flash-success" role="status"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg><span>{{ session('success') }}</span></div>@endif
  @if(session('error'))<div class="flash flash-error" role="alert"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v5M12 16.5v.5"/><circle cx="12" cy="12" r="9"/></svg><span>{{ session('error') }}</span></div>@endif
  @if($errors->any())<div class="flash flash-error" role="alert"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v5M12 16.5v.5"/><circle cx="12" cy="12" r="9"/></svg><div><strong>Please check the following</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
  @yield('content')
  <footer class="site-footer"><span>ZU HUB <b>© {{ date('Y') }}</b> · MADE FOR THE NEXT GENERATION</span><span>@yield('footer_right', 'Zarqa University')</span></footer>
</main>

<x-confirm-modal />
@foreach(['zu-glass', 'zu-header', 'zu-notifications', 'zu-alerts', 'zu-modal', 'zu-admin'] as $script)
<script src="{{ asset("js/{$script}.js") }}?v={{ @filemtime(public_path("js/{$script}.js")) }}"></script>
@endforeach
@stack('scripts')
</body>
</html>
