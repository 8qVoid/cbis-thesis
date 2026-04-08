<?php

namespace App\Console\Commands;

use App\Models\BloodInventory;
use Illuminate\Console\Command;

class FlagExpiredInventory extends Command
{
    protected $signature = 'inventory:flag-expired';

    protected $description = 'Auto-flag expired blood inventory records';

    public function handle(): int
    {
        $count = BloodInventory::query()
            ->whereDate('expiration_date', '<', now()->toDateString())
            ->where('status', '!=', 'expired')
            ->update(['status' => 'expired']);

        $this->info("Expired items flagged: {$count}");

        return self::SUCCESS;
    }
}
