<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterNotificationsRequest;
use App\Notifications\LowStockAlert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(FilterNotificationsRequest $request): View
    {
        $filters = $request->validated();
        $user = $request->user();
        $status = (string) ($filters['status'] ?? 'all');

        $query = $user->notifications()->where('type', LowStockAlert::class);

        if ($status === 'unread') {
            $query->whereNull('read_at');
        }

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        $notifications = $query->latest()->paginate(20)->withQueryString();

        return view('notifications.index', compact('notifications', 'status'));
    }

    public function markRead(Request $request, string $id): RedirectResponse
    {
        /** @var DatabaseNotification|null $notification */
        $notification = $request->user()
            ->notifications()
            ->where('type', LowStockAlert::class)
            ->whereKey($id)
            ->first();

        if (! $notification) {
            abort(404);
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()
            ->unreadNotifications()
            ->where('type', LowStockAlert::class)
            ->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
