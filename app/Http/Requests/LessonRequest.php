<?php

namespace App\Http\Requests;

use App\Services\LessonFileService;
use App\Services\LessonService;
use App\Support\TeamsUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Add or edit one piece of course content. There are exactly four kinds, and each has one job:
 *
 *   video  an uploaded MP4 (a file, never a link)
 *   file   a material: an image, PDF, document, presentation or spreadsheet (a file)
 *   link   an external resource: any http(s) address, including YouTube (never stored, never downloaded)
 *   live   a Microsoft Teams meeting with a date and time
 *
 * Authorisation runs BEFORE validation, so a student, an administrator or another instructor can
 * never learn anything from validation messages. The kind is chosen when the content is created and
 * cannot change afterwards, so an edit always validates the lesson's own kind. An older "video"
 * that is only a link (a saved YouTube lesson) counts as an external resource (LessonService::kindOf).
 */
class LessonRequest extends FormRequest
{
    public ?object $course = null;
    public ?object $lesson = null;

    public function authorize(): bool
    {
        $lessons = app(LessonService::class);
        $offeringId = (string) $this->route('offeringId');

        $this->course = $lessons->ownedCourse($this->user(), $offeringId);
        abort_unless($lessons->available(), 409, 'Course sections are not set up yet.');

        if ($this->route('lessonId')) {
            $this->lesson = $lessons->ownedLesson($this->user(), $offeringId, (string) $this->route('lessonId'));
        }

        return true;
    }

    public function creating(): bool
    {
        return $this->lesson === null;
    }

    public function contentType(): string
    {
        return $this->creating() ? (string) $this->input('lesson_type') : LessonService::kindOf($this->lesson);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'lesson_title' => is_string($this->input('lesson_title')) ? trim($this->input('lesson_title')) : $this->input('lesson_title'),
            'description' => is_string($this->input('description')) ? trim($this->input('description')) : $this->input('description'),
        ]);
    }

    public function rules(): array
    {
        $type = $this->contentType();
        $files = app(LessonFileService::class);

        $rules = [
            'lesson_title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            // the section the content belongs to; it must be one of THIS course's sections
            'section_id' => ['nullable', 'string', 'max:50', Rule::exists('lesson_sections', 'section_id')->where('offering_id', $this->course->offering_id)],
        ];

        if ($this->creating()) {
            $rules['lesson_type'] = ['required', Rule::in(LessonService::TYPES)];
        }

        if ($type === 'link') {
            $rules['lesson_url'] = ['required', 'string', 'max:2000', 'url:http,https'];
        }

        if ($type === 'video') {
            // an uploaded MP4 only; a file is needed unless an edit keeps the one already there
            $needsFile = $this->creating() || empty($this->lesson->file_path);
            $rules['file'] = [
                $needsFile ? 'required' : 'nullable',
                'file',
                'max:'.(int) config('lessons.video_max_kb', LessonFileService::VIDEO_MAX_KILOBYTES),
                function (string $attribute, mixed $value, \Closure $fail) use ($files) {
                    if ($value instanceof UploadedFile && ($problem = $files->problem($value, LessonFileService::VIDEO))) {
                        $fail($problem);
                    }
                },
            ];
        }

        if ($type === 'live') {
            $unchanged = ! $this->creating() ? $this->lesson->live_url : null;
            $rules['live_url'] = [
                'required', 'string', 'max:2000', 'url:https',
                function (string $attribute, mixed $value, \Closure $fail) use ($unchanged) {
                    // new or changed links must be Microsoft Teams links; an older link that is left as it was stays valid
                    if (is_string($value) && $value !== $unchanged && ! TeamsUrl::isValid($value)) {
                        $fail('Paste the Microsoft Teams meeting link (it starts with https://teams.microsoft.com/).');
                    }
                },
            ];
            $rules['scheduled_at'] = ['required', 'date'];
        }

        if ($type === 'file') {
            $rules['file'] = [
                $this->creating() ? 'required' : 'nullable',
                'file',
                'max:'.(int) config('lessons.document_max_kb', LessonFileService::MAX_KILOBYTES),
                function (string $attribute, mixed $value, \Closure $fail) use ($files) {
                    if ($value instanceof UploadedFile && ($problem = $files->problem($value, LessonFileService::DOCUMENT))) {
                        $fail($problem);
                    }
                },
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        $files = app(LessonFileService::class);
        $limit = $this->contentType() === 'video' ? LessonFileService::VIDEO : LessonFileService::DOCUMENT;

        return [
            'lesson_title.required' => 'Give this content a title.',
            'lesson_title.max' => 'The title is too long (255 characters at most).',
            'description.max' => 'The description is too long (2000 characters at most).',
            'section_id.exists' => 'Choose one of this course\'s sections.',
            'lesson_type.required' => 'Choose what you want to add.',
            'lesson_type.in' => 'Choose what you want to add.',
            'lesson_url.required' => 'Paste the link to the resource.',
            'lesson_url.url' => 'Enter a full web address that starts with http:// or https://.',
            'live_url.required' => 'Paste the Microsoft Teams meeting link.',
            'live_url.url' => 'Paste the Microsoft Teams meeting link (it starts with https://teams.microsoft.com/).',
            'scheduled_at.required' => 'Choose the date and time of the session.',
            'scheduled_at.date' => 'Choose a valid date and time.',
            'file.required' => $this->contentType() === 'video' ? 'Choose an MP4 video to upload.' : 'Choose a file to upload.',
            'file.file' => 'Choose a file to upload.',
            'file.uploaded' => 'The file could not be uploaded. It may be larger than this server allows ('.$files->humanSize($files->serverLimitBytes()).').',
            'file.max' => 'This file is too large. The limit is '.$files->humanSize($files->maxBytes($limit)).'.',
        ];
    }

    /** The lessons-table values for this content (only the columns that belong to its kind). */
    public function values(): array
    {
        $data = $this->validated();
        $type = $this->contentType();

        return [
            'lesson_title' => $data['lesson_title'],
            'description' => ($data['description'] ?? '') !== '' ? $data['description'] : null,
            'lesson_url' => $type === 'link' ? $data['lesson_url'] : null,
            'live_url' => $type === 'live' ? $data['live_url'] : null,
            'scheduled_at' => $type === 'live' ? Carbon::parse($data['scheduled_at'])->format('Y-m-d H:i:s') : null,
        ];
    }

    /** The purpose a posted file is checked and stored for. */
    public function filePurpose(): string
    {
        return $this->contentType() === 'video' ? LessonFileService::VIDEO : LessonFileService::DOCUMENT;
    }
}
