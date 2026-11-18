<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InstructorCourseManagementController extends Controller
{
    private function ownOffering(Request $request, string $id)
    {
        abort_unless(
            $request->user() &&
            $request->user()->role === 'instructor',
            403
        );

        $offering = DB::table('Course_Offerings')
            ->where('offering_id', $id)
            ->where('instructor_id', $request->user()->user_id)
            ->first();

        abort_unless($offering, 404);
        abort_unless($offering->approval_status === 'approved', 403, 'Course needs admin approval first.');

        return $offering;
    }

    public function destroy(Request $request, string $offeringId)
    {
        $this->ownOffering($request, $offeringId);

        return DB::transaction(function () use ($request, $offeringId) {
            $course = DB::table('Course_Offerings')
                ->where('offering_id', $offeringId)
                ->where('instructor_id', $request->user()->user_id)
                ->lockForUpdate()
                ->first();

            abort_unless($course, 404);

            $hasSaved = DB::table('user_saved_courses')->where('offering_id',$offeringId)->exists();

            $hasStudents = DB::table('Course_Members')
                ->where('offering_id', $offeringId)
                ->exists();

            $hasLessons = DB::table('Lessons')
                ->where('offering_id', $offeringId)
                ->exists();

            $hasReviews = DB::table('Reviews')
                ->where('offering_id', $offeringId)
                ->exists();

            $hasNotifications = DB::table('Notifications')
                ->where('offering_id', $offeringId)
                ->exists();

            if (
                $hasSaved ||
                $hasStudents ||
                $hasLessons ||
                $hasReviews ||
                $hasNotifications
            ) {
                return redirect()
                    ->route('instructor.courses')
                    ->with(
                        'error',
                        'This course has students, lessons or other related data, so it cannot be deleted.'
                    );
            }

            DB::table('Course_Offerings')
                ->where('offering_id', $offeringId)
                ->where('instructor_id', $request->user()->user_id)
                ->delete();

            app(\App\Services\CourseCoverService::class)->delete($course->cover_image ?? null);

            return redirect()
                ->route('instructor.courses')
                ->with('success', 'Course deleted successfully.');
        });
    }

}
