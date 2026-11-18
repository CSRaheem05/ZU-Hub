<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * A course's cover: the uploaded image, or a generated sky-blue fallback.
 *
 * Every course without an upload gets a stable, good-looking cover (palette + icon derived
 * from its id), so cards never look empty and the same course always looks the same.
 *
 * Usage: <x-course-cover :course="$course" variant="card" />   variants: card | wide | thumb | preview
 */
class CourseCover extends Component
{
    public const PALETTES = 6;

    public ?string $url = null;
    public int $palette = 0;
    public int $icon = 0;

    public function __construct(public object|array $course, public string $variant = 'card', public ?string $alt = null)
    {
        $id = (string) $this->field('offering_id');
        $cover = $this->field('cover_image');

        $this->palette = abs(crc32($id)) % self::PALETTES;
        $this->icon = abs(crc32(strrev($id))) % 6;

        if ($id !== '' && $cover) {
            // the file name is part of the query string, so replacing the image busts any cache
            $this->url = route('courses.cover', ['offeringId' => $id]).'?v='.substr(md5($cover), 0, 10);
        }
    }

    private function field(string $key): mixed
    {
        return is_array($this->course) ? ($this->course[$key] ?? null) : ($this->course->{$key} ?? null);
    }

    public function altText(): string
    {
        return $this->alt ?? 'Cover image for '.($this->field('title') ?: 'the course');
    }

    public function render(): View
    {
        return view('components.course-cover');
    }
}
