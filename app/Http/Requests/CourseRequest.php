<?php

namespace App\Http\Requests;

use App\Services\CourseCoverService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Create / edit a course (instructors only).
 *
 *  - create                  all fields  → submitted for approval
 *  - edit, changes_requested all fields  → resubmitted for approval
 *  - edit, approved          only the details that do not change what the administration approved
 *                            (description, duration, schedule, cover). Title, subject and topic are ignored
 *                            by the server even if someone posts them.
 *
 * Authorization runs first: another instructor's course is a 404, a course in the wrong state a 403.
 */
class CourseRequest extends FormRequest
{
    /** @var object|null the course being edited, loaded once by authorize() */
    private ?object $course = null;

    public function authorize(): bool
    {
        $user = $this->user();
        abort_unless($user && $user->role === 'instructor', 403);

        $id = $this->route('offeringId');
        if ($id !== null) {
            $this->course = DB::table('course_offerings')
                ->where('offering_id', $id)->where('instructor_id', $user->user_id)->first();
            abort_unless($this->course, 404);
            abort_unless(in_array($this->course->approval_status, ['changes_requested', 'approved'], true), 403, 'This course cannot be edited right now.');
        }

        return true;
    }

    /** Approved courses keep the title / subject / topic the administration approved. */
    public function isLimitedEdit(): bool
    {
        return $this->course !== null && $this->course->approval_status === 'approved';
    }

    public function rules(): array
    {
        $rules = [
            'description' => ['nullable', 'string', 'max:5000'],
            'duration_hours' => ['nullable', 'numeric', 'min:0.01', 'max:9999.99'],
            'lecture_schedule' => ['nullable', 'string', 'max:2000'],
            'cover_image' => [
                'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp',
                'max:'.CourseCoverService::MAX_KILOBYTES,
                'dimensions:min_width='.CourseCoverService::MIN_WIDTH.',min_height='.CourseCoverService::MIN_HEIGHT
                    .',max_width='.CourseCoverService::MAX_SIDE.',max_height='.CourseCoverService::MAX_SIDE,
            ],
            'remove_cover' => ['nullable', 'boolean'],
        ];

        if (! $this->isLimitedEdit()) {
            $rules += [
                'course_id' => ['required', Rule::exists('courses', 'course_id')],
                'topic_id' => ['nullable', Rule::exists('topics', 'topic_id')],
                'title' => ['required', 'string', 'max:255'],
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'course_id.required' => 'Please choose the university subject this course relates to.',
            'cover_image.image' => 'The cover must be an image (JPG, PNG or WebP).',
            'cover_image.mimes' => 'The cover must be a JPG, PNG or WebP image.',
            'cover_image.max' => 'The cover is too large. Please use an image under 5 MB.',
            'cover_image.dimensions' => 'The cover must be at least '.CourseCoverService::MIN_WIDTH.'×'.CourseCoverService::MIN_HEIGHT.' px (and no larger than '.CourseCoverService::MAX_SIDE.' px).',
            'cover_image.uploaded' => 'The cover could not be uploaded. Please try a smaller image.',
        ];
    }

    /** The chosen topic must belong to the same college as the chosen subject. */
    public function after(): array
    {
        return [function (Validator $validator) {
            $topic = $this->input('topic_id');
            if ($this->isLimitedEdit() || ! $topic || $validator->errors()->hasAny(['course_id', 'topic_id'])) {
                return;
            }

            $college = DB::table('courses as c')->join('departments as d', 'd.department_code', '=', 'c.department_code')
                ->where('c.course_id', $this->input('course_id'))->value('d.college_code');

            if (! DB::table('topics')->where('topic_id', $topic)->where('college_code', $college)->exists()) {
                $validator->errors()->add('topic_id', 'The topic must belong to the same college as the selected subject.');
            }
        }];
    }

    /** Fields the controller may write (already validated). */
    public function details(): array
    {
        $data = $this->validated();

        $fields = [
            'description' => $data['description'] ?? null,
            'duration_hours' => $data['duration_hours'] ?? null,
            'lecture_schedule' => $data['lecture_schedule'] ?? null,
        ];

        if (! $this->isLimitedEdit()) {
            $fields += [
                'course_id' => $data['course_id'],
                'topic_id' => $data['topic_id'] ?? null,
                'title' => $data['title'],
            ];
        }

        return $fields;
    }

    public function course(): ?object
    {
        return $this->course;
    }
}
