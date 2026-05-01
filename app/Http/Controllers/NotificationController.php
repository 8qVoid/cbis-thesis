<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterNotificationsRequest;
use App\Models\User;
use App\Notifications\FacilityApplicationSubmitted;
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

        $notificationTypes = $this->notificationTypesFor($user);
        $query = $user->notifications()->whereIn('type', $notificationTypes);

        if (! $user->isCentralAdmin()) {
            $query->where('data->facility_id', $user->facility_id);
        }

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
            ->whereIn('type', $this->notificationTypesFor($request->user()))
            ->when(! $request->user()->isCentralAdmin(), function ($query) use ($request): void {
                $query->where('data->facility_id', $request->user()->facility_id);
            })
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
            ->whereIn('type', $this->notificationTypesFor($request->user()))
            ->when(! $request->user()->isCentralAdmin(), function ($query) use ($request): void {
                $query->where('data->facility_id', $request->user()->facility_id);
            })
            ->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * @return array<int, class-string>
     */
    private function notificationTypesFor(User $user): array
    {
        if ($user->isCentralAdmin()) {
            return [FacilityApplicationSubmitted::class];
        }

        return [LowStockAlert::class];
    }
}
