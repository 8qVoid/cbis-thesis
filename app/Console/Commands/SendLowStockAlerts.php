<?php

namespace App\Console\Commands;

use App\Models\BloodInventory;
use App\Models\User;
use App\Notifications\LowStockAlert;
use Illuminate\Console\Command;

class SendLowStockAlerts extends Command
{
    protected $signature = 'inventory:notify-low-stock';

    protected $description = 'Send component-aware low stock notifications to QAO and assigned Blood Bank Staff';

    public function handle(): int
    {
        $defaultThreshold = (int) env('LOW_STOCK_THRESHOLD', 5);

        // If stock has recovered, return status from low_stock to active.
        BloodInventory::query()
            ->where('status', 'low_stock')
            ->whereRaw("units_available > CASE component WHEN 'whole_blood' THEN 20 WHEN 'packed_red_blood_cells' THEN 20 WHEN 'platelet_concentrate' THEN 5 WHEN 'fresh_frozen_plasma' THEN 10 ELSE ? END", [$defaultThreshold])
            ->whereDate('expiration_date', '>=', now()->toDateString())
            ->update([
                'status' => 'active',
                'last_low_stock_alert_at' => null,
            ]);

        $lowStockItems = BloodInventory::query()
            ->with('facility')
            ->whereRaw("units_available <= CASE component WHEN 'whole_blood' THEN 20 WHEN 'packed_red_blood_cells' THEN 20 WHEN 'platelet_concentrate' THEN 5 WHEN 'fresh_frozen_plasma' THEN 10 ELSE ? END", [$defaultThreshold])
            ->whereDate('expiration_date', '>=', now()->toDateString())
            ->where(function ($query): void {
                $query
                    ->where('status', 'active')
                    ->orWhere(function ($nested): void {
                        $nested
                            ->where('status', 'low_stock')
                            ->whereNull('last_low_stock_alert_at');
                    });
            })
            ->get();

        $facilityAlertStaff = User::query()
            ->where('is_active', true)
            ->whereNotNull('facility_id')
            ->where(function ($query): void {
                $query
                    ->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'Blood Bank Staff'))
                    ->orWhereHas('roles.permissions', fn ($permissionQuery) => $permissionQuery->where('name', 'manage inventory'))
                    ->orWhereHas('permissions', fn ($permissionQuery) => $permissionQuery->where('name', 'manage inventory'));
            })
            ->get()
            ->groupBy('facility_id');

        $centralAdmins = User::query()
            ->where('is_active', true)
            ->whereNull('facility_id')
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Quality Assurance Officer', 'Super Administrator']))
            ->get();

        foreach ($lowStockItems as $item) {
            $recipients = $facilityAlertStaff
                ->get($item->facility_id, collect())
                ->merge($centralAdmins);

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
