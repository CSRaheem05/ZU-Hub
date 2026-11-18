{{-- Scripts the shared header needs. Put just before </body> of any page that uses the site-header component. --}}
@foreach(['zu-glass', 'zu-header', 'zu-notifications', 'zu-alerts'] as $script)
<script src="{{ asset("js/{$script}.js") }}?v={{ @filemtime(public_path("js/{$script}.js")) }}"></script>
@endforeach
