{{-- Stylesheets the shared header needs. Put inside <head> of any page that uses the site-header component. --}}
@foreach(['zu-glass', 'zu-header', 'zu-notifications', 'zu-alerts', 'zu-course-cover'] as $sheet)
<link rel="stylesheet" href="{{ asset("css/{$sheet}.css") }}?v={{ @filemtime(public_path("css/{$sheet}.css")) }}">
@endforeach
<style>.zu-header-spacer{height:var(--zu-header-h,100px)}</style>
