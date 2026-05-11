<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('event_registrations')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE event_registrations MODIFY status ENUM('registered', 'cancelled', 'attended', 'no_show') NOT NULL DEFAULT 'registered'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('event_registrations')) {
            return;
        }

        DB::table('event_registrations')
            ->whereIn('status', ['attended', 'no_show'])
            ->update(['status' => 'registered']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE event_registrations MODIFY status ENUM('registered', 'cancelled') NOT NULL DEFAULT 'registered'");
        }
    }
};
