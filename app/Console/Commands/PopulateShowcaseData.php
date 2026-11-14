<?php

namespace App\Console\Commands;

use Database\Seeders\ShowcaseSeeder;
use Illuminate\Console\Command;

class PopulateShowcaseData extends Command
{
    protected $signature = 'showcase:populate';

    protected $description = 'Add connected synthetic records to a local CBIS database';

    public function handle(): int
    {
        if (! app()->environment('local', 'testing')) {
            $this->error('Showcase records can only be added in a local or testing environment.');

            return self::FAILURE;
        }

        $this->call('migrate', ['--force' => true]);
        $this->call('db:seed', ['--class' => ShowcaseSeeder::class, '--force' => true]);
        $this->info('Connected local showcase records are ready. Running this command again will not duplicate them.');

        return self::SUCCESS;
    }
}
