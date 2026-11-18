<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnnouncementRequest;
use App\Models\Announcement;
use App\Services\AnnouncementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Admin and instructor announcements. Access is decided by AnnouncementPolicy;
 * recipients are decided by AnnouncementService; the schedule by AnnouncementRequest.
 * None of those come from trusting the form.
 */
class AnnouncementController extends Controller
{
    public function __construct(private readonly AnnouncementService $announcements)
    {
    }

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Announcement::class);
        $user = $request->user();
        $filter = array_key_exists((string) $request->query('status'), Announcement::STATES) ? $request->query('status') : 'all';

        $base = Announcement::query()
            // Administrators moderate everything; instructors see only their own.
            ->when($user->role !== 'manager', fn ($q) => $q->where('author_id', $user->user_id));

        $counts = ['all' => (clone $base)->count()];
        foreach (array_keys(Announcement::STATES) as $state) {
            $counts[$state] = (clone $base)->inState($state)->count();
        }

        $items = $base
            ->inState($filter)
            ->with(['author:user_id,name', 'offering:offering_id,title'])
            ->withCount(['notifications as recipients_count', 'notifications as read_count' => fn ($q) => $q->where('is_read', true)])
            ->orderByDesc('created_at')
            ->get();

        return view('announcements.index', compact('items', 'filter', 'counts'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Announcement::class);

        return view('announcements.form', $this->formData($request, new Announcement(['priority' => 'normal'])));
    }

    public function store(AnnouncementRequest $request): RedirectResponse
    {
        Gate::authorize('create', Announcement::class);
        $user = $request->user();
        $data = $request->validated();
        $schedule = $request->schedule();

        $announcement = Announcement::create([
            'author_id' => $user->user_id,
            'author_role' => $user->role,
            'audience' => $data['audience'],
            'offering_id' => $data['audience'] === 'course' ? $data['offering_id'] : null,
            'title' => $data['title'],
            'body' => $data['body'],
            'priority' => $data['priority'],
            'status' => 'draft',
            'publish_at' => $schedule['publish_at'] ?? null,
            'expires_at' => $schedule['expires_at'] ?? null,
        ]);

        if ($request->input('action') === 'publish') {
            return $this->publishAndRedirect($announcement);
        }

        return redirect()->route('announcements.index', ['status' => 'draft'])->with('success', 'Draft saved. Publish it when you are ready.');
    }

    public function edit(Request $request, Announcement $announcement): View
    {
        Gate::authorize('update', $announcement);

        return view('announcements.form', $this->formData($request, $announcement));
    }

    public function update(AnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        Gate::authorize('update', $announcement);
        $schedule = $request->schedule();
        $startLocked = $announcement->isPublished() && ! $announcement->isScheduled(); // already live: start time is history

        $announcement->fill($request->safe()->only(['title', 'body', 'priority']));
        if (array_key_exists('expires_at', $schedule)) {
            $announcement->expires_at = $schedule['expires_at'];
        }

        if (! $startLocked && array_key_exists('publish_at', $schedule)) {
            if ($announcement->isPublished()) {           // scheduled: move the go-live time
                $announcement->save();
                $this->announcements->reschedule($announcement, $schedule['publish_at']);
            } else {                                      // draft: remember it for when it is published
                $announcement->publish_at = $schedule['publish_at'];
            }
        }
        $announcement->save();

        if ($request->input('action') === 'publish' && ! $announcement->isPublished()) {
            return $this->publishAndRedirect($announcement);
        }

        return redirect()->route('announcements.index')->with('success', 'Announcement updated.');
    }

    public function publish(Announcement $announcement): RedirectResponse
    {
        Gate::authorize('publish', $announcement);

        return $this->publishAndRedirect($announcement);
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        Gate::authorize('delete', $announcement);
        $announcement->delete(); // recipients' copies are removed by the foreign-key cascade

        return redirect()->route('announcements.index')->with('success', 'Announcement deleted.');
    }

    private function publishAndRedirect(Announcement $announcement): RedirectResponse
    {
        $count = $this->announcements->publish($announcement);

        if ($count === null) {
            return redirect()->route('announcements.index')->with('success', 'This announcement was already published.');
        }

        $people = $count.' '.Str::plural('person', $count);
        $message = match (true) {
            $count === 0 => 'Published, but there is nobody to notify yet.',
            $announcement->isScheduled() => 'Scheduled. It will reach '.$people.' at the chosen time.',
            default => 'Published and sent to '.$people.'.',
        };

        return redirect()
            ->route('announcements.index', ['status' => $announcement->isScheduled() ? 'scheduled' : 'live'])
            ->with('success', $message);
    }

    private function formData(Request $request, Announcement $announcement): array
    {
        $role = $request->user()->role;

        return [
            'announcement' => $announcement,
            'audiences' => $this->announcements->audiencesFor($role),
            'priorities' => $this->announcements->prioritiesFor($role),
            'offerings' => $role === 'instructor' ? $this->announcements->offeringsFor($request->user()) : collect(),
        ];
    }
}
