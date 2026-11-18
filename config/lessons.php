<?php

/*
|--------------------------------------------------------------------------
| Course content storage
|--------------------------------------------------------------------------
| Uploaded materials (documents) and recorded videos are stored through Laravel's Filesystem, never in MySQL
| (only the path and a few metadata fields are kept in the `lessons` table).
|
| To move to the university's cloud storage later, define a disk in config/filesystems.php (for example an
| "s3" disk) and set LESSON_FILES_DISK=s3 in .env. Files already uploaded must be copied to that disk first;
| no code or database change is needed. Playback and downloads keep going through the authorised route
| (LessonFileController), which redirects to a short-lived signed URL when the disk supports it.
*/
return [
    'disk' => env('LESSON_FILES_DISK', 'local'),

    'directory' => 'lesson-files',

    // Limits (kilobytes). The server's php.ini limits still apply on top of these.
    'document_max_kb' => 20 * 1024,   // PDF, Word, PowerPoint, Excel, text
    'video_max_kb' => 50 * 1024,      // short MP4 demo videos

    // Lifetime of the signed URL used when the disk is a cloud disk
    'temporary_url_minutes' => 10,
];
