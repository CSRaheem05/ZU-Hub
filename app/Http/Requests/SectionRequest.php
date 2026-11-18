<?php

namespace App\Http\Requests;

use App\Services\LessonService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Create or rename a course section. Only the instructor who owns the (approved) course may do this;
 * authorisation runs before validation, so nobody else learns anything from validation messages.
 */
class SectionRequest extends FormRequest
{
    public ?object $course = null;
    public ?object $section = null;

    public function authorize(): bool
    {
        $lessons = app(LessonService::class);
        $offeringId = (string) $this->route('offeringId');

        $this->course = $lessons->ownedCourse($this->user(), $offeringId);
        abort_unless($lessons->available(), 409, 'Course sections are not set up yet.');

        if ($this->route('sectionId')) {
            $this->section = $lessons->ownedSection($this->user(), $offeringId, (string) $this->route('sectionId'));
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        $title = $this->input('title');
        $this->merge(['title' => is_string($title) ? trim(preg_replace('/\s+/u', ' ', $title) ?? '') : $title]);
    }

    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:255']];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Give the section a name.',
            'title.max' => 'The name is too long (255 characters at most).',
        ];
    }
}
