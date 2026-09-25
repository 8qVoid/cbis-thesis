<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterNotificationsRequest;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use App\Notifications\ActivityReviewStatusChanged;
use App\Notifications\BloodReservationStatusChanged;
use App\Notifications\BloodReservationSubmitted;
use App\Notifications\DonorScreeningUpdated;
use App\Notifications\EventPostedNotification;
use App\Notifications\LowStockAlert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
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

        $this->limitToUserFacility($query, $user);

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
        $query = $request->user()
            ->notifications()
            ->whereIn('type', $this->notificationTypesFor($request->user()));

        $this->limitToUserFacility($query, $request->user());
        $notification = $query->whereKey($id)->first();

        if (! $notification) {
            abort(404);
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return back()->with('success', 'Notification marked as read.');
    }

    public function open(Request $request, string $id): RedirectResponse
    {
        /** @var DatabaseNotification|null $notification */
        $query = $request->user()
            ->notifications()
            ->whereIn('type', $this->notificationTypesFor($request->user()));

        $this->limitToUserFacility($query, $request->user());
        $notification = $query->whereKey($id)->first();

        if (! $notification) {
            abort(404);
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return redirect()->to($this->notificationUrl($notification, $request->user()));
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $query = $request->user()
            ->unreadNotifications()
            ->whereIn('type', $this->notificationTypesFor($request->user()));

        $this->limitToUserFacility($query, $request->user());
        $query->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    private function notificationUrl(DatabaseNotification $notification, User $user): string
    {
        $data = $notification->data ?? [];

        if (in_array($notification->type, [BloodReservationSubmitted::class, BloodReservationStatusChanged::class], true)) {
            if (! empty($data['reservation_id']) && Route::has('reservations.show')) {
                return route('reservations.show', $data['reservation_id']);
            }

            return route('reservations.index');
        }

        if ($notification->type === LowStockAlert::class) {
            return route('blood-inventory.index', [
                'component' => array_search($data['component'] ?? '', \App\Models\BloodInventory::COMPONENTS, true) ?: null,
                'status' => 'low_stock',
            ]);
        }

        if ($notification->type === ActivityReviewStatusChanged::class) {
            if (! empty($data['activity_id']) && Route::has('donation-schedules.show')) {
                return route('donation-schedules.show', $data['activity_id']);
            }

            return route('donation-schedules.index');
        }

        if ($notification->type === EventPostedNotification::class) {
            return route('public.map', array_filter([
                'event_type' => $data['event_type'] ?? null,
                'event_date' => $data['event_date'] ?? null,
            ]));
        }

        if ($notification->type === DonorScreeningUpdated::class) {
            return route('account.dashboard', ['view' => 'donor']);
        }

        return route('notifications.index');
    }

    /**
     * @return array<int, class-string>
     */
    private function notificationTypesFor(User $user): array
    {
        if ($user->isQao()) {
            return [LowStockAlert::class, BloodReservationSubmitted::class];
        }
        if ($user->isBloodBankStaff()) {
            return [LowStockAlert::class, BloodReservationSubmitted::class];
        }
        if ($user->isEventFacilitator()) {
            return [ActivityReviewStatusChanged::class];
        }

        $types = [];
        if ($user->hasPatientAccess()) {
            $types[] = BloodReservationStatusChanged::class;
        }
        if ($user->hasDonorAccess()) {
            $types[] = EventPostedNotification::class;
            $types[] = DonorScreeningUpdated::class;
        }

        return $types;
    }

    private function limitToUserFacility($query, User $user): void
    {
        if ($user->isCentralAdmin() || $user->hasAnyRole(['Donor', 'Patient'])) {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            $query->whereRaw("(data::jsonb ->> 'facility_id') = ?", [(string) $user->facility_id]);

            return;
        }

        $query->where('data->facility_id', $user->facility_id);
    }

    /**
     * @return class-string|null
     */
    private function notificationClassForFilter(string $type): ?string
    {
        return match ($type) {
            'low_stock' => LowStockAlert::class,
            'reservation' => BloodReservationSubmitted::class,
            'reservation_status' => BloodReservationStatusChanged::class,
            'activity' => ActivityReviewStatusChanged::class,
            'event' => EventPostedNotification::class,
            'screening' => DonorScreeningUpdated::class,
            default => null,
        };
    }
}
