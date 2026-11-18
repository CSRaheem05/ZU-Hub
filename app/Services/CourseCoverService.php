<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Stores and removes course cover images on Laravel's "public" disk.
 *
 * Security notes
 *  - The file type is decided from the file's CONTENT (finfo), never from the client's
 *    filename or declared MIME type, and only JPEG / PNG / WebP are accepted (no SVG: it can carry script).
 *  - The stored name is random and its extension comes from the detected type, so an
 *    upload can never choose its own name, extension or directory.
 *  - Files are served through CourseCoverController (authorization + nosniff headers),
 *    not from a guessable public URL, so no `storage:link` is needed.
 */
class CourseCoverService
{
    public const DISK = 'public';
    public const DIRECTORY = 'course-covers';

    /** detected MIME type => extension */
    public const TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /** Validation limits shared with the form request and the browser-side checks. */
    public const MAX_KILOBYTES = 5120;
    public const MIN_WIDTH = 640;
    public const MIN_HEIGHT = 360;
    public const MAX_SIDE = 6000;

    /** @return string the path to keep in course_offerings.cover_image */
    public function store(UploadedFile $file): string
    {
        $mime = $this->detectMime($file->getRealPath());

        if (! isset(self::TYPES[$mime])) {
            throw ValidationException::withMessages(['cover_image' => 'The cover must be a JPG, PNG or WebP image.']);
        }

        $name = Str::random(40).'.'.self::TYPES[$mime];
        Storage::disk(self::DISK)->putFileAs(self::DIRECTORY, $file, $name, 'public');

        return self::DIRECTORY.'/'.$name;
    }

    /** Removes a stored cover. Anything outside the covers directory is ignored. */
    public function delete(?string $path): void
    {
        if ($path && str_starts_with($path, self::DIRECTORY.'/') && ! str_contains($path, '..')) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    public function exists(?string $path): bool
    {
        return $path
            && str_starts_with($path, self::DIRECTORY.'/')
            && ! str_contains($path, '..')
            && Storage::disk(self::DISK)->exists($path);
    }

    private function detectMime(string $realPath): ?string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $realPath) : null;
        if ($finfo) {
            finfo_close($finfo);
        }

        return $mime ?: null;
    }
}
