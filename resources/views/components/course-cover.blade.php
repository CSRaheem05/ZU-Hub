{{-- A course cover: uploaded image, or a generated fallback. Styles: css/zu-course-cover.css --}}
<div {{ $attributes->class(['zu-cover', 'zu-cover--'.$variant]) }}>
  @if($url)
    <img src="{{ $url }}" alt="{{ $altText() }}" loading="lazy" decoding="async">
  @else
    <div class="zu-cover__art zu-cover__art--{{ $palette }}" aria-hidden="true">
      <svg class="zu-cover__waves" viewBox="0 0 640 360" preserveAspectRatio="xMidYMid slice">
        <g fill="none" stroke="#fff" stroke-linecap="round">
          <path d="M-40 340C110 215 240 262 360 176S556 58 700 8" stroke-width="2.4" opacity=".7"/>
          <path d="M-40 372C120 250 250 296 372 212S570 98 700 52" stroke-width="1.4" opacity=".5"/>
          <path d="M-40 308C100 186 230 226 348 142S544 22 700-34" stroke-width="3.6" opacity=".4"/>
          <path d="M-40 396C130 286 262 330 386 252S584 146 700 100" stroke-width="1.2" opacity=".38"/>
        </g>
      </svg>
      <svg class="zu-cover__glyph" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.15" stroke-linecap="round" stroke-linejoin="round">
        @switch($icon)
          @case(0) <path d="M4 5.500A1.500 1.500 0 0 1 5.500 4H20v14H5.500A1.500 1.500 0 0 0 4 19.500z"/><path d="M4 19.500A1.500 1.500 0 0 0 5.500 21H20M8.500 8h7M8.500 11.500h5"/> @break
          @case(1) <path d="m8 8-4 4 4 4M16 8l4 4-4 4M13.500 5l-3 14"/> @break
          @case(2) <path d="M9 3h6M10 3v6.500L4.800 18.200A2 2 0 0 0 6.500 21h11a2 2 0 0 0 1.700-2.800L14 9.500V3M7.500 15h9"/> @break
          @case(3) <circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 4 6 4 9s-1 6-4 9c-3-3-4-6-4-9s1-6 4-9z"/> @break
          @case(4) <path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/> @break
          @default <path d="M9 18h6M10 21h4M12 3a6 6 0 0 0-3.500 10.900c.6.500 1 1.200 1 2V16h5v-.100c0-.8.400-1.500 1-2A6 6 0 0 0 12 3z"/>
        @endswitch
      </svg>
    </div>
  @endif
</div>
