<?php

namespace App\Console\Commands;

use App\Models\BloodInventory;
use App\Models\User;
use App\Notifications\LowStockAlert;
use Illuminate\Console\Command;

class SendLowStockAlerts extends Command
{
    protected $signature = 'inventory:notify-low-stock';

    protected $description = 'Send low stock notifications to facility admins and central admin';

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

        $centralAdmins = User::query()
            ->where('is_active', true)
            ->whereNull('facility_id')
            ->whereHas('roles', fn ($query) => $query->where('name', 'Central Administrator'))
            ->get();

        $facilityAdmins = User::query()
            ->where('is_active', true)
            ->whereNotNull('facility_id')
            ->whereHas('roles', fn ($query) => $query->where('name', 'Facility Admin / Blood Bank Personnel'))
            ->get()
            ->groupBy('facility_id');

        foreach ($lowStockItems as $item) {
            $recipients = $centralAdmins->concat($facilityAdmins->get($item->facility_id, collect()));

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
