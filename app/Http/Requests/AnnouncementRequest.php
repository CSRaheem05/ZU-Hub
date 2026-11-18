<?php

namespace App\Http\Requests;

use App\Models\Announcement;
use App\Services\AnnouncementService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Validates creating / editing an announcement.
 *
 *  - Allowed audiences, priorities and courses depend on the author's role; an instructor can
 *    only pick one of their OWN courses.
 *  - Only administrators may schedule (publish_at) or expire (expires_at). Instructors that send
 *    those fields get a validation error, so this cannot be bypassed from the form.
 *  - Times arrive as ISO-8601 (the browser converts the picked local time to UTC).
 *
 * Authorization comes first: a student gets 403, not a form error.
 */
class AnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $announcement = $this->route('announcement');

        if (! $user) {
            return false;
        }

        return $announcement instanceof Announcement
            ? $user->can('update', $announcement)
            : $user->can('create', Announcement::class);
    }

    public function rules(): array
    {
        $role = $this->user()->role;
        $announcement = $this->route('announcement');
        $editing = $announcement instanceof Announcement;

        $rules = [
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(AnnouncementService::ROLE_PRIORITIES[$role] ?? [])],
        ];

        // Audience and course are fixed once an announcement exists, so recipients never drift.
        if (! $editing) {
            $rules['audience'] = ['required', Rule::in(AnnouncementService::ROLE_AUDIENCES[$role] ?? [])];
            $rules['offering_id'] = [
                'nullable', 'required_if:audience,course',
                Rule::exists('course_offerings', 'offering_id')
                    ->where('instructor_id', $this->user()->user_id)
                    ->where('approval_status', 'approved'),
            ];
        }

        if ($role !== 'manager') {
            foreach (['send_mode', 'expire_mode', 'publish_at', 'expires_at'] as $field) {
                $rules[$field] = ['prohibited'];
            }

            return $rules;
        }

        // Once an announcement is live its start time is history; only the end time can still change.
        $startLocked = $editing && $announcement->isPublished() && ! $announcement->isScheduled();
        $later = ! $startLocked && $this->input('send_mode') === 'later';

        $rules['expire_mode'] = ['nullable', Rule::in(['never', 'date'])];
        $rules['expires_at'] = [
            'nullable', 'date', 'required_if:expire_mode,date',
            function (string $attribute, mixed $value, \Closure $fail) use ($later) {
                $end = $this->parseTime($value);
                $start = $later ? $this->parseTime($this->input('publish_at')) : now();
                if ($end && $start && $end->lessThanOrEqualTo($start)) {
                    $fail($later ? 'The end time must be after the start time.' : 'The end time must be in the future.');
                }
            },
        ];

        if (! $startLocked) {
            $rules['send_mode'] = ['nullable', Rule::in(['now', 'later'])];
            $rules['publish_at'] = [
                'nullable', 'date', 'required_if:send_mode,later',
                function (string $attribute, mixed $value, \Closure $fail) use ($later) {
                    $start = $this->parseTime($value);
                    // one minute of grace: a time picked "right now" is already a few seconds old on arrival
                    if ($later && $start && $start->lessThan(now()->subMinute())) {
                        $fail('The start time must be in the future.');
                    }
                },
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'offering_id.required_if' => 'Please choose which of your courses this announcement is for.',
            'offering_id.exists' => 'You can only send announcements to your own approved courses.',
            'audience.in' => 'You are not allowed to send to that audience.',
            'publish_at.required_if' => 'Please pick when the announcement should start.',
            'expires_at.required_if' => 'Please pick when the announcement should end.',
            'prohibited' => 'Only administrators can schedule or expire announcements.',
        ];
    }

    /**
     * The validated schedule as UTC times. `publish_at` is null for "publish immediately";
     * `expires_at` is null for "never expires". Keys are only present when the form sent them.
     */
    public function schedule(): array
    {
        $out = [];

        if ($this->has('send_mode')) {
            $out['publish_at'] = $this->input('send_mode') === 'later' ? $this->parseTime($this->input('publish_at')) : null;
        }
        if ($this->has('expire_mode')) {
            $out['expires_at'] = $this->input('expire_mode') === 'date' ? $this->parseTime($this->input('expires_at')) : null;
        }

        return $out;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['title' => trim((string) $this->input('title')), 'body' => trim((string) $this->input('body'))]);
    }

    private function parseTime(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->utc();
        } catch (\Throwable) {
            return null;
        }
    }
}
