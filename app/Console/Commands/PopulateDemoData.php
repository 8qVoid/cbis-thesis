<?php

namespace App\Console\Commands;

use App\Services\DemoDataScenario;
use Database\Seeders\FacilitySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Console\Command;

class PopulateDemoData extends Command
{
    protected $signature = 'demo:populate';

    protected $description = 'Create a repeatable, connected CBIS demonstration scenario';

    public function handle(DemoDataScenario $scenario): int
    {
        if (! config('demo.enabled')) {
            $this->error('Demo data is disabled. Set DEMO_DATA_ENABLED=true only on the intended demo environment.');

            return self::FAILURE;
        }

        $this->call('db:seed', ['--class' => RolePermissionSeeder::class, '--force' => true]);
        $this->call('db:seed', ['--class' => FacilitySeeder::class, '--force' => true]);

        $summary = $scenario->populate();

        $this->info('CBIS demo scenario is ready.');
        $this->table(['Record', 'Count'], collect($summary)->map(fn (int $count, string $label): array => [$label, $count]));

        return self::SUCCESS;
    }
}
