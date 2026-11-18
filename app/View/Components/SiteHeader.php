<?php

namespace App\View\Components;

use Illuminate\Support\Facades\DB;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * The shared glass header: account on the left, navigation in the centre,
 * Zarqa University logo on the right (English layout).
 *
 * Usage: <x-site-header />
 */
class SiteHeader extends Component
{
    private const ROLE_LABELS = [
        'student' => 'Student',
        'instructor' => 'Instructor',
        'manager' => 'Administrator',
    ];

    /**
     * @param bool $language Show the language button (id="languageButton"). Pages whose own script
     *                       already toggles English/Arabic on that id keep working unchanged.
     */
    public function __construct(public bool $language = false)
    {
    }

    public function user()
    {
        return auth()->user();
    }

    public function roleLabel(): string
    {
        return self::ROLE_LABELS[$this->user()?->role] ?? 'User';
    }

    /**
     * Navigation items after the Home icon, chosen by the signed-in role.
     * `match` holds the route-name patterns that mark an item as active.
     *
     * @return array<int, array{label:string, url:string, active:bool, badge:?int}>
     */
    public function links(): array
    {
        $items = match ($this->user()?->role) {
            'manager' => [
                ['label' => 'Dashboard', 'route' => 'management.index', 'match' => ['management.index']],
                ['label' => 'Course Requests', 'route' => 'management.requests', 'match' => ['management.requests*'], 'badge' => $this->pendingRequests()],
                ['label' => 'Announcements', 'route' => 'announcements.index', 'match' => ['announcements.*']],
            ],
            'instructor' => [
                ['label' => 'Courses', 'route' => 'courses.index', 'match' => ['courses.*']],
                ['label' => 'My Courses', 'route' => 'instructor.courses', 'match' => ['instructor.*']],
                ['label' => 'Announcements', 'route' => 'announcements.index', 'match' => ['announcements.*']],
            ],
            'student' => [
                ['label' => 'Courses', 'route' => 'courses.index', 'match' => ['courses.*']],
                ['label' => 'My Courses', 'route' => 'student.courses', 'match' => ['student.*']],
            ],
            default => [],
        };

        return array_map(fn (array $item) => [
            'label' => $item['label'],
            'url' => route($item['route']),
            'active' => request()->routeIs(...$item['match']),
            'badge' => ($item['badge'] ?? 0) ?: null,
        ], $items);
    }

    private function pendingRequests(): int
    {
        return DB::table('course_offerings')->where('approval_status', 'pending')->count();
    }

    public function render(): View
    {
        return view('components.site-header');
    }
}
