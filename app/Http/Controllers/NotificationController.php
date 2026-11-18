<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The signed-in user's notification inbox. Every query is scoped to the current user and to
 * what is live for them (scheduled announcements stay hidden until their go-live time), so
 * nobody can read, mark or even discover someone else's / not-yet-published notifications.
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->user()->user_id;
        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';

        $notifications = UserNotification::query()->inbox($userId)
            ->when($filter === 'unread', fn ($q) => $q->active()->unread())
            ->paginate(12)->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'filter' => $filter,
            'unread' => UserNotification::unreadCountFor($userId),
            'total' => UserNotification::query()->where('user_id', $userId)->visible()->count(),
        ]);
    }

    /** Opening a notification marks it as read (which also quiets the bell alert). */
    public function show(Request $request, string $notificationId): View
    {
        $notification = $this->owned($request, $notificationId);
        $notification->markRead();

        return view('notifications.show', ['notification' => $notification]);
    }

    public function read(Request $request, string $notificationId): JsonResponse|RedirectResponse
    {
        $this->owned($request, $notificationId)->markRead();

        return $this->respond($request);
    }

    public function readAll(Request $request): JsonResponse|RedirectResponse
    {
        UserNotification::query()->where('user_id', $request->user()->user_id)->visible()->unread()
            ->update(['is_read' => true, 'read_at' => now()]);

        return $this->respond($request);
    }

    /**
     * The slide-down banner reports which alerts it just showed, so each announcement
     * is announced once per user (not again on the next page).
     */
    public function alertsSeen(Request $request): JsonResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array', 'max:50'], 'ids.*' => ['string', 'max:50']])['ids'];

        UserNotification::query()->where('user_id', $request->user()->user_id)
            ->whereIn('notification_id', $ids)->whereNull('alert_seen_at')
            ->update(['alert_seen_at' => now()]);

        return response()->json(['ok' => true]);
    }

    private function owned(Request $request, string $notificationId): UserNotification
    {
        return UserNotification::query()->inbox($request->user()->user_id)
            ->whereKey($notificationId)->firstOrFail(); // 404 for anyone else's (or a not-yet-live) notification
    }

    private function respond(Request $request): JsonResponse|RedirectResponse
    {
        $userId = $request->user()->user_id;
        $unread = UserNotification::unreadCountFor($userId);

        return $request->expectsJson()
            ? response()->json(['unread' => $unread, 'alerts' => UserNotification::alertCountFor($userId), 'level' => UserNotification::alertLevelFor($userId)])
            : back()->with('success', $unread === 0 ? 'All notifications marked as read.' : null);
    }
}
