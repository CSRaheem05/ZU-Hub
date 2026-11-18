{{--
  The identity glyph of a notification. Shape (not only colour) tells the priority apart:
  normal = shield + check, important = circle + exclamation, urgent = triangle + exclamation. Instructors get their initial.
  Props: $official (bool), $priority, $sender.
--}}
@if(! $official){{ strtoupper(mb_substr($sender, 0, 1)) }}
@elseif($priority === 'urgent')<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.3 4.2 2.9 17.5A2 2 0 0 0 4.6 20.500h14.800a2 2 0 0 0 1.700-3L13.700 4.200a2 2 0 0 0-3.400 0z"/><path d="M12 9.500v4M12 16.700v.1"/></svg>
@elseif($priority === 'important')<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7.500v5M12 15.800v.1"/></svg>
@else<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4.500 6v5.500c0 4.500 3.100 8.200 7.500 9.500 4.400-1.300 7.500-5 7.500-9.500V6z"/><path d="m9 12 2.200 2.200L15.500 10"/></svg>
@endif
