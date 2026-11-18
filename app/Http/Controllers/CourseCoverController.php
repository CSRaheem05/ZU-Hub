<?php

namespace App\Http\Controllers;

use App\Services\CourseCoverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves course cover images (the same URL is used by instructor, student and admin pages).
 *
 * Visible to any signed-in user once the course is approved; before that only to the course's
 * instructor and to administrators. The file path always comes from the database, never from the URL.
 */
class CourseCoverController extends Controller
{
    public function show(Request $request, string $offeringId, CourseCoverService $covers): StreamedResponse
    {
        $course = DB::table('course_offerings')->where('offering_id', $offeringId)
            ->first(['offering_id', 'instructor_id', 'approval_status', 'cover_image']);

        abort_unless($course && $covers->exists($course->cover_image), 404);

        $user = $request->user();
        abort_unless(
            $course->approval_status === 'approved'
                || $user->role === 'manager'
                || $user->user_id === $course->instructor_id,
            404
        );

        return Storage::disk(CourseCoverService::DISK)->response($course->cover_image, null, [
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
