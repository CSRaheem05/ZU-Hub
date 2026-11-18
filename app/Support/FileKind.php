<?php

namespace App\Support;

/**
 * The single place that decides how a file type looks to students: every extension maps to a family
 * (pdf, word, powerpoint, excel, text, file), which fixes its label, its colour class (.cl-k--{family} in
 * zu-course-learn.css) and its document glyph. Presentation only: nothing here touches stored data.
 */
class FileKind
{
    /** extension => family */
    private const FAMILY = [
        'pdf' => 'pdf',
        'doc' => 'word', 'docx' => 'word',
        'ppt' => 'powerpoint', 'pptx' => 'powerpoint',
        'xls' => 'excel', 'xlsx' => 'excel',
        'txt' => 'text',
    ];

    private const LABEL = [
        'pdf' => 'PDF', 'word' => 'Word', 'powerpoint' => 'PowerPoint', 'excel' => 'Excel', 'text' => 'Text', 'file' => 'Material',
    ];

    /** The mark on the page: a letter (or short word) drawn in the family colour. [text, font-size, y] */
    private const MARK = [
        'pdf' => ['PDF', 9.5, 28.2],
        'word' => ['W', 16, 29.5],
        'powerpoint' => ['P', 16, 29.5],
        'excel' => ['X', 16, 29.5],
    ];

    public static function family(?string $ext): string
    {
        return self::FAMILY[strtolower((string) $ext)] ?? 'file';
    }

    public static function label(?string $ext): string
    {
        return self::LABEL[self::family($ext)];
    }

    /** A white document page with a folded corner and the family's mark; colours come from CSS (.cl-glyph). */
    public static function glyph(?string $ext): string
    {
        $family = self::family($ext);
        $page = '<path class="g-page" d="M11 5h13l7 7v21a2 2 0 0 1-2 2H11a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z"/><path class="g-fold" d="M24 5v5a2 2 0 0 0 2 2h5z"/>';

        if (isset(self::MARK[$family])) {
            [$t, $size, $y] = self::MARK[$family];
            $mark = '<text class="g-ink" x="20" y="'.$y.'" text-anchor="middle" font-size="'.$size.'" font-weight="800" letter-spacing="'.($family === 'pdf' ? '.3' : '0').'">'.$t.'</text>';
        } else {
            // text / unknown: ruled lines
            $mark = '<path class="g-line" d="M14 20h12M14 25h12M14 30h7"/>';
        }

        return '<svg class="fk-glyph" viewBox="0 0 40 40" aria-hidden="true" focusable="false">'.$page.$mark.'</svg>';
    }

    /** Colour family for any lesson form: documents by extension, everything else by its own form (image, video, youtube, link, live). */
    public static function familyFor(string $form, ?string $ext = null): string
    {
        return $form === 'file' ? self::family($ext) : $form;
    }

    /** The inside of a .fk-tile: a document glyph for files, a play badge for videos, the YouTube mark, or a stroke icon. */
    public static function tile(string $form, ?string $ext = null): string
    {
        return match ($form) {
            'file' => self::glyph($ext),
            'video' => '<svg class="fk-glyph" viewBox="0 0 40 40" aria-hidden="true" focusable="false"><circle class="g-page" cx="20" cy="20" r="12.5"/><path class="g-ink" d="M17 14.5v11l9.5-5.5z"/></svg>',
            'youtube' => '<svg class="fk-glyph" viewBox="0 0 40 40" aria-hidden="true" focusable="false"><rect class="g-page" x="4" y="10" width="32" height="20" rx="6.5"/><path class="g-ink" d="M16.5 14.8v10.4l9-5.2z"/></svg>',
            default => LessonIcons::svg($form),
        };
    }
}
