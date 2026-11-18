<?php

namespace App\Http\Controllers;

use App\Services\LessonFileService;
use App\Services\LessonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Serves an uploaded course file (a material or a recorded MP4 video). The file path always comes from the
 * database, never from the URL.
 *
 * Access follows the course page: the owning instructor; a student who is enrolled in an ongoing
 * course; any student for a completed course. Administrators and everyone else are refused.
 *
 * Videos are streamed with HTTP Range support (seeking works in the player). PDFs, MP4 videos and JPG / PNG / WebP
 * images may open inline with ?view=1; every other file downloads.
 */
class LessonFileController extends Controller
{
    public function show(Request $request, string $lessonId, LessonService $lessons, LessonFileService $files): Response
    {
        $lesson = DB::table('lessons')->where('lesson_id', $lessonId)->first();

        abort_unless($lesson && in_array($lesson->lesson_type, ['file', 'video'], true) && $files->exists($lesson->file_path), 404);

        $course = DB::table('course_offerings')->where('offering_id', $lesson->offering_id)->first();

        abort_unless($course, 404);
        abort_unless($lessons->canOpenFile($request->user(), $course), 403);

        $inline = $request->boolean('view') && in_array($lesson->file_mime, ['application/pdf', 'video/mp4', 'image/jpeg', 'image/png', 'image/webp'], true);
        $name = $lesson->file_name ?: basename($lesson->file_path);
        $disposition = $inline ? 'inline' : 'attachment';
        $headers = [
            'Content-Type' => $lesson->file_mime ?: 'application/octet-stream',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ];

        $disk = $files->disk();

        // Local (private) disk: a file response gives Range requests, ETag and Last-Modified for free.
        if (method_exists($disk, 'getAdapter') && $disk->getAdapter() instanceof \League\Flysystem\Local\LocalFilesystemAdapter) {
            // A Range that starts after the end of the file can never be satisfied: answer 416 as HTTP requires.
            $size = (int) @filesize($disk->path($lesson->file_path));
            if (preg_match('/^bytes=(\d+)-/', (string) $request->header('Range'), $m) && (int) $m[1] >= $size) {
                return response('', 416, ['Content-Range' => "bytes */{$size}"] + $headers);
            }

            $fallback = str_replace(['%', '/', '\\'], '_', preg_replace('/[^\x20-\x7E]/', '_', Str::ascii($name)) ?? 'file');

            return response()->file($disk->path($lesson->file_path), $headers)
                ->setContentDisposition($disposition, $name, $fallback);
        }

        // Cloud disk (for example the university's storage later): send the browser to a short-lived signed URL,
        // which handles Range itself. The authorisation above has already happened.
        try {
            return redirect()->away($disk->temporaryUrl(
                $lesson->file_path,
                now()->addMinutes((int) config('lessons.temporary_url_minutes', 10)),
                ['ResponseContentDisposition' => $disposition.'; filename="'.addcslashes($name, '"\\').'"', 'ResponseContentType' => $headers['Content-Type']]
            ));
        } catch (Throwable $e) {
            // the disk cannot sign URLs: stream the whole file through the application
            return $disk->response($lesson->file_path, $name, $headers, $disposition);
        }
    }
}
