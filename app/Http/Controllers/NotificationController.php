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
        $alertType = (string) ($filters['type'] ?? 'all');

        $notificationTypes = $this->notificationTypesFor($user);
        $selectedType = $this->notificationClassForFilter($alertType);

        if ($selectedType !== null && in_array($selectedType, $notificationTypes, true)) {
            $notificationTypes = [$selectedType];
        }

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

        return view('notifications.index', compact('notifications', 'status', 'alertType'));
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
            return [FacilityApplicationSubmitted::class, LowStockAlert::class];
        }

        return [LowStockAlert::class];
    }

    /**
     * @return class-string|null
     */
    private function notificationClassForFilter(string $type): ?string
    {
        return match ($type) {
            'facility_application' => FacilityApplicationSubmitted::class,
            'low_stock' => LowStockAlert::class,
            default => null,
        };
    }
}
