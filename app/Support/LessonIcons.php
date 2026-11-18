<?php

namespace App\Support;

/** The stroke icons (24x24) and the names of the content types used by the course-content pages. */
class LessonIcons
{
    private const PATHS = [
        'video' => '<rect x="3" y="5" width="18" height="14" rx="4"/><path d="m10.5 9.5 4 2.5-4 2.5z"/>',
        'file' => '<path d="M7 3h7l5 5v12a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>',
        'link' => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
        'live' => '<circle cx="12" cy="12" r="2.2"/><path d="M8 8a5.7 5.7 0 0 0 0 8M16 8a5.7 5.7 0 0 1 0 8M5 5a10 10 0 0 0 0 14M19 5a10 10 0 0 1 0 14"/>',
        'grip' => '<circle cx="9" cy="5.5" r="1.7"/><circle cx="15" cy="5.5" r="1.7"/><circle cx="9" cy="12" r="1.7"/><circle cx="15" cy="12" r="1.7"/><circle cx="9" cy="18.5" r="1.7"/><circle cx="15" cy="18.5" r="1.7"/>',
        'edit' => '<path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>',
        'trash' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-12M9 7V4h6v3"/>',
        'lock' => '<rect x="5" y="11" width="14" height="9" rx="2.5"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
        'eye' => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'back' => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'x' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'upload' => '<path d="M12 16V5M7.5 9.5 12 5l4.5 4.5M5 19h14"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'check' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 4 6 4 9s-1 6-4 9c-3-3-4-6-4-9s1-6 4-9z"/>',
        'chevron' => '<path d="m6 9 6 6 6-6"/>',
        'more' => '<circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/>',
        'up' => '<path d="M12 19V5M6 11l6-6 6 6"/>',
        'down' => '<path d="M12 5v14M6 13l6 6 6-6"/>',
        'move' => '<path d="M12 3v18M3 12h18M8 7l4-4 4 4M8 17l4 4 4-4M7 8l-4 4 4 4M17 8l4 4-4 4"/>',
        'folder' => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2.5h8a2 2 0 0 1 2 2V18a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'layers' => '<path d="m12 3 9 5-9 5-9-5zM3 13l9 5 9-5"/>',
        'download' => '<path d="M12 4v11M7.5 10.5 12 15l4.5-4.5M5 19h14"/>',
        'play' => '<path d="M8 5.5v13l11-6.5z"/>',
        'user' => '<circle cx="12" cy="8" r="3.5"/><path d="M4.5 20c.6-3.6 3.4-5.500 7.500-5.500s6.900 1.900 7.500 5.500"/>',
        'expand' => '<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>',
        'external' => '<path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
        'image' => '<rect x="3" y="4" width="18" height="16" rx="3.5"/><circle cx="9" cy="10" r="1.8"/><path d="m21 16-5.2-5.2a1.5 1.5 0 0 0-2.1 0L5 19.5"/>',
        'calendar' => '<rect x="3.5" y="5" width="17" height="15" rx="3"/><path d="M8 3v4M16 3v4M3.5 10h17"/>',
    ];

    public static function svg(string $name, string $class = ''): string
    {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"'.($class ? ' class="'.$class.'"' : '').'>'.(self::PATHS[$name] ?? '').'</svg>';
    }

    /** The four kinds of content: label and a short hint for the picker. */
    public static function kinds(): array
    {
        return [
            'video' => ['Video', 'Upload a recorded video (MP4)'],
            'file' => ['Material', 'Images, PDFs and documents'],
            'link' => ['External resource', 'YouTube or any website'],
            'live' => ['Live session', 'A Microsoft Teams meeting'],
        ];
    }
}
