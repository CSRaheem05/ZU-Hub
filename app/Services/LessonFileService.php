<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use ZipArchive;

/**
 * Stores, checks and removes the files instructors upload to a course: documents (materials) and short MP4 videos.
 *
 * Storage
 *  - Files go through Laravel's Filesystem on the disk named in config/lessons.php (LESSON_FILES_DISK), the
 *    private "local" disk by default, so the university's cloud storage can replace it later without code changes.
 *  - MySQL only holds the path and metadata, never file content.
 *
 * Security
 *  - Files are never public: they are only reachable through LessonFileController, which applies the course access rules.
 *  - The type is decided from the file's CONTENT (finfo magic bytes, plus a look inside Office packages / the MP4
 *    header), never from the client's filename or declared MIME type. Only short whitelists are accepted:
 *    documents (pdf, doc, docx, ppt, pptx, xls, xlsx, txt) and video (mp4). No HTML, SVG, scripts, archives, macros.
 *  - The stored name is a random UUID and its extension comes from the whitelist: an upload can never choose its own
 *    name, extension or directory (no traversal, no double extensions).
 *  - Deletion only ever touches files this service created (strict path + name check) and never a file another
 *    lesson still points to; a failed delete is logged, it never breaks the request.
 */
class LessonFileService
{
    public const DOCUMENT = 'document';
    public const VIDEO = 'video';

    /** Product limits: 20 MB per document, 50 MB per video (kept in config/lessons.php, mirrored here for callers). */
    public const MAX_BYTES = 20 * 1024 * 1024;
    public const MAX_KILOBYTES = 20480;
    public const VIDEO_MAX_BYTES = 50 * 1024 * 1024;
    public const VIDEO_MAX_KILOBYTES = 51200;

    private const OLE = ['application/vnd.ms-office', 'application/x-ole-storage', 'application/CDFV2', 'application/CDFV2-corrupt'];
    // some libmagic versions report a genuine Office package as zip / octet-stream: the package structure is checked below
    private const ZIP = ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'];
    private const MP4_BRANDS = ['isom', 'iso2', 'iso3', 'iso4', 'iso5', 'iso6', 'mp41', 'mp42', 'avc1', 'dash', 'MSNV', 'M4V ', 'F4V '];

    /**
     * extension => [label, accepted detected MIME types, the MIME type we store and serve, kind, required package part]
     * "kind": pdf | ole (legacy Office) | ooxml (modern Office, a zip package) | text | image | mp4
     */
    public const DOCUMENT_TYPES = [
        'pdf'  => ['PDF',        ['application/pdf'], 'application/pdf', 'pdf', null],
        'doc'  => ['Word',       ['application/msword'], 'application/msword', 'ole', null],
        'docx' => ['Word',       ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'], 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'ooxml', 'word/document.xml'],
        'ppt'  => ['PowerPoint', ['application/vnd.ms-powerpoint'], 'application/vnd.ms-powerpoint', 'ole', null],
        'pptx' => ['PowerPoint', ['application/vnd.openxmlformats-officedocument.presentationml.presentation'], 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'ooxml', 'ppt/presentation.xml'],
        'xls'  => ['Excel',      ['application/vnd.ms-excel'], 'application/vnd.ms-excel', 'ole', null],
        'xlsx' => ['Excel',      ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'], 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'ooxml', 'xl/workbook.xml'],
        'txt'  => ['Text',       ['text/plain'], 'text/plain', 'text', null],
        'jpg'  => ['Image',      ['image/jpeg'], 'image/jpeg', 'image', null],
        'jpeg' => ['Image',      ['image/jpeg'], 'image/jpeg', 'image', null],
        'png'  => ['Image',      ['image/png'], 'image/png', 'image', null],
        'webp' => ['Image',      ['image/webp'], 'image/webp', 'image', null],
    ];

    public const VIDEO_TYPES = [
        'mp4' => ['MP4 video', ['video/mp4', 'video/x-m4v'], 'video/mp4', 'mp4', null],
    ];

    /** Kept for existing callers: the document whitelist. */
    public const TYPES = self::DOCUMENT_TYPES;

    // ------------------------------------------------------------ storage

    public function diskName(): string
    {
        return (string) config('lessons.disk', 'local');
    }

    public function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }

    public function directory(): string
    {
        return trim((string) config('lessons.directory', 'lesson-files'), '/');
    }

    // ------------------------------------------------------------ what is accepted

    private function types(string $purpose): array
    {
        return $purpose === self::VIDEO ? self::VIDEO_TYPES : self::DOCUMENT_TYPES;
    }

    /** e.g. ".pdf,.doc,.docx" for the browser's file picker */
    public function accept(string $purpose = self::DOCUMENT): string
    {
        return '.'.implode(',.', array_keys($this->types($purpose)));
    }

    public function describeTypes(string $purpose = self::DOCUMENT): string
    {
        return $purpose === self::VIDEO ? 'MP4 videos' : 'images (JPG, PNG, WebP), PDF, Word, PowerPoint, Excel or text files';
    }

    public function maxBytes(string $purpose = self::DOCUMENT): int
    {
        return ($purpose === self::VIDEO ? (int) config('lessons.video_max_kb', self::VIDEO_MAX_KILOBYTES) : (int) config('lessons.document_max_kb', self::MAX_KILOBYTES)) * 1024;
    }

    /**
     * Checks an uploaded file. Returns a human message when it is NOT acceptable, null when it is.
     */
    public function problem(UploadedFile $file, string $purpose = self::DOCUMENT): ?string
    {
        if (! $file->isValid()) {
            return $this->uploadErrorMessage($file->getError());
        }

        if ($file->getSize() > $this->maxBytes($purpose)) {
            return 'This file is too large. The limit is '.$this->humanSize($this->maxBytes($purpose)).'.';
        }

        if ($file->getSize() === 0) {
            return 'This file is empty.';
        }

        return $this->inspect($file->getRealPath(), (string) $file->getClientOriginalExtension(), $purpose) === null
            ? 'This file type is not supported. Upload '.$this->describeTypes($purpose).'.'
            : null;
    }

    /**
     * @return array{ext:string,label:string,mime:string}|null  null = not an accepted file
     */
    public function inspect(string $path, string $clientExtension, string $purpose = self::DOCUMENT): ?array
    {
        $ext = strtolower($clientExtension);
        $types = $this->types($purpose);

        if (! isset($types[$ext]) || ! is_file($path)) {
            return null;
        }

        [$label, $detected, $serve, $kind, $part] = $types[$ext];

        // An unreadable or vanished file simply counts as "not a supported file".
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? (string) (@finfo_file($finfo, $path) ?: '') : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        $head = (string) (@file_get_contents($path, false, null, 0, 8192) ?: '');

        $ok = match ($kind) {
            'pdf'   => $mime === 'application/pdf' && str_starts_with($head, '%PDF-'),
            'ole'   => (in_array($mime, $detected, true) || in_array($mime, self::OLE, true))
                        && str_starts_with($head, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"),
            'ooxml' => (in_array($mime, $detected, true) || in_array($mime, self::ZIP, true))
                        && str_starts_with($head, "PK\x03\x04")
                        && $this->hasPackagePart($path, $part),
            'text'  => $mime === 'text/plain' && ! str_contains($head, "\0"),
            // a real, decodable picture of the type its name says (JPEG, PNG or WebP: never SVG, GIF or anything scriptable)
            'image' => in_array($mime, $detected, true) && $this->looksLikeImage($path, $head, $serve),
            // an MP4 is an ISO base media file: a "ftyp" box with a known brand first, then a well-formed box structure
            'mp4'   => in_array($mime, $detected, true)
                        && strlen($head) >= 12 && substr($head, 4, 4) === 'ftyp'
                        && in_array(substr($head, 8, 4), self::MP4_BRANDS, true)
                        && $this->hasMp4Structure($path),
            default => false,
        };

        return $ok ? ['ext' => $ext, 'label' => $label, 'mime' => $serve] : null;
    }

    /**
     * Walks the top-level boxes of an MP4: every box must be well formed and together they must cover the file
     * exactly (so a truncated upload or a file with a payload appended is refused), and the file must contain the
     * movie header (moov) and media data (mdat, or moof for fragmented files).
     */
    private function hasMp4Structure(string $path): bool
    {
        $size = @filesize($path);
        $handle = @fopen($path, 'rb');

        if (! $size || ! $handle) {
            return false;
        }

        $position = 0;
        $seen = [];

        for ($i = 0; $i < 1000 && $position < $size; $i++) {
            fseek($handle, $position);
            $header = (string) fread($handle, 16);

            if (strlen($header) < 8) {
                break;
            }

            $length = unpack('N', substr($header, 0, 4))[1];
            $type = substr($header, 4, 4);

            if ($length === 1) {                       // 64-bit box size
                $length = strlen($header) === 16 ? unpack('J', substr($header, 8, 8))[1] : 0;
            } elseif ($length === 0) {                 // the box runs to the end of the file
                $length = $size - $position;
            }

            if ($length < 8 || $position + $length > $size || ! preg_match('/^[ -~]{4}$/', $type)) {
                fclose($handle);

                return false;
            }

            $seen[$type] = true;
            $position += $length;
        }

        fclose($handle);

        return $position === $size && isset($seen['ftyp'], $seen['moov']) && (isset($seen['mdat']) || isset($seen['moof']));
    }

    /** Magic bytes of the claimed format, plus a header that decodes to real, sane dimensions of that same format. */
    private function looksLikeImage(string $path, string $head, string $mime): bool
    {
        $signature = match ($mime) {
            'image/jpeg' => str_starts_with($head, "\xFF\xD8\xFF"),
            'image/png' => str_starts_with($head, "\x89PNG\r\n\x1a\n"),
            'image/webp' => strlen($head) >= 12 && substr($head, 0, 4) === 'RIFF' && substr($head, 8, 4) === 'WEBP',
            default => false,
        };

        if (! $signature) {
            return false;
        }

        $info = @getimagesize($path);

        return is_array($info) && $info[0] > 0 && $info[1] > 0 && $info[0] * $info[1] <= 100_000_000 && ($info['mime'] ?? '') === $mime;
    }

    /** A real Office package contains [Content_Types].xml and its main part (word/document.xml ...). */
    private function hasPackagePart(string $path, string $part): bool
    {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            return false;
        }

        $ok = $zip->locateName('[Content_Types].xml') !== false && $zip->locateName($part) !== false;
        $zip->close();

        return $ok;
    }

    /**
     * Saves a file that already passed problem().
     *
     * @return array{file_path:string,file_name:string,file_size:int,file_mime:string}
     */
    public function store(UploadedFile $file, string $purpose = self::DOCUMENT): array
    {
        $info = $this->inspect($file->getRealPath(), (string) $file->getClientOriginalExtension(), $purpose);
        abort_if($info === null, 422, 'This file type is not supported.');

        $name = (string) Str::uuid().'.'.$info['ext'];
        $path = $this->disk()->putFileAs($this->directory(), $file, $name);
        abort_unless($path, 500, 'The file could not be saved.');

        return [
            'file_path' => $path,
            'file_name' => $this->cleanName($file->getClientOriginalName(), $info['ext']),
            'file_size' => (int) $file->getSize(),
            'file_mime' => $info['mime'],
        ];
    }

    public function exists(?string $path): bool
    {
        return $this->isOurs($path) && $this->disk()->exists($path);
    }

    /**
     * Removes a stored file. Safe by construction:
     *  - only paths that look exactly like the ones store() creates;
     *  - never a file some lesson still references;
     *  - any failure is logged and reported as false, never thrown.
     */
    public function delete(?string $path): bool
    {
        if (! $this->isOurs($path)) {
            return false;
        }

        try {
            if (DB::table('lessons')->where('file_path', $path)->exists()) {
                return false;
            }

            return $this->disk()->delete($path);
        } catch (Throwable $e) {
            Log::warning('Could not delete a lesson file.', ['path' => $path, 'error' => $e->getMessage()]);

            return false;
        }
    }

    private function isOurs(?string $path): bool
    {
        $extensions = implode('|', array_merge(array_keys(self::DOCUMENT_TYPES), array_keys(self::VIDEO_TYPES)));

        return is_string($path)
            && preg_match('~^'.preg_quote($this->directory(), '~').'/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.('.$extensions.')$~', $path) === 1;
    }

    /** A safe display name: no path, no control characters, never empty, always with the real extension. */
    public function cleanName(string $original, string $ext): string
    {
        $base = pathinfo(str_replace('\\', '/', $original), PATHINFO_FILENAME);
        $base = preg_replace('/[\x00-\x1F\x7F<>:"\/\\\\|?*]+/u', ' ', $base) ?? '';
        $base = trim(preg_replace('/\s+/u', ' ', $base) ?? '', " .\t");
        $base = $base === '' ? 'Material' : Str::limit($base, 120, '');

        return $base.'.'.$ext;
    }

    // ------------------------------------------------------------ limits

    /** What PHP itself will accept per upload (the smaller of upload_max_filesize and post_max_size). */
    public function serverLimitBytes(): int
    {
        $limits = array_filter([
            $this->toBytes((string) ini_get('upload_max_filesize')),
            $this->toBytes((string) ini_get('post_max_size')),
        ], fn ($v) => $v > 0);

        return $limits ? min($limits) : PHP_INT_MAX;
    }

    /** What an instructor can really upload right now: the product limit, capped by the server's. */
    public function effectiveMaxBytes(string $purpose = self::DOCUMENT): int
    {
        return min($this->maxBytes($purpose), $this->serverLimitBytes());
    }

    public function toBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $number = (float) $value;
        $unit = strtolower(substr($value, -1));

        return (int) match ($unit) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }

    public function humanSize(int $bytes): string
    {
        return match (true) {
            $bytes >= 1024 ** 2 => rtrim(rtrim(number_format($bytes / 1024 ** 2, 1), '0'), '.').' MB',
            $bytes >= 1024      => max(1, (int) round($bytes / 1024)).' KB',
            default             => $bytes.' B',
        };
    }

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'This file is larger than the server allows ('.$this->humanSize($this->serverLimitBytes()).').',
            UPLOAD_ERR_PARTIAL => 'The upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_FILE => 'Choose a file to upload.',
            default => 'The file could not be uploaded. Please try again.',
        };
    }
}
