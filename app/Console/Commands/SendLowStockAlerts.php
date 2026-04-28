<?php

namespace App\Console\Commands;

use App\Models\BloodInventory;
use App\Models\User;
use App\Notifications\LowStockAlert;
use Illuminate\Console\Command;

class SendLowStockAlerts extends Command
{
    protected $signature = 'inventory:notify-low-stock';

    protected $description = 'Send low stock notifications to facility facilitators and medical staff';

    public function handle(): int
    {
        $threshold = (int) env('LOW_STOCK_THRESHOLD', 5);

        // If stock has recovered, return status from low_stock to active.
        BloodInventory::query()
            ->where('status', 'low_stock')
            ->where('units_available', '>', $threshold)
            ->whereDate('expiration_date', '>=', now()->toDateString())
            ->update(['status' => 'active']);

        $lowStockItems = BloodInventory::query()
            ->with('facility')
            ->where('units_available', '<=', $threshold)
            ->where('status', 'active')
            ->whereDate('expiration_date', '>=', now()->toDateString())
            ->get();

        $facilityAlertStaff = User::query()
            ->where('is_active', true)
            ->whereNotNull('facility_id')
            ->where(function ($query): void {
                $query
                    ->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'Facilitator'))
                    ->orWhereHas('roles.permissions', fn ($permissionQuery) => $permissionQuery->where('name', 'manage inventory'))
                    ->orWhereHas('permissions', fn ($permissionQuery) => $permissionQuery->where('name', 'manage inventory'));
            })
            ->get()
            ->groupBy('facility_id');

        foreach ($lowStockItems as $item) {
            $recipients = $facilityAlertStaff->get($item->facility_id, collect());

            foreach ($recipients->unique('id') as $user) {
                $user->notify(new LowStockAlert($item));
            }

            $item->update([
                'status' => 'low_stock',
                'last_low_stock_alert_at' => now(),
            ]);
        }

        $this->info('Low stock notifications dispatched: '.$lowStockItems->count());

        return self::SUCCESS;
    }
}
