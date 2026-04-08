<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('donation_schedules', function (Blueprint $table) {
            $table->enum('event_type', ['blood_donation', 'bloodletting'])->default('blood_donation')->after('title');
            $table->date('event_date')->nullable()->after('event_type');
            $table->time('start_time')->nullable()->after('event_date');
            $table->time('end_time')->nullable()->after('start_time');
            $table->decimal('latitude', 10, 7)->nullable()->after('venue');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->text('description')->nullable()->after('longitude');
            $table->string('contact_person')->nullable()->after('description');
            $table->enum('status', ['planned', 'ongoing', 'completed', 'cancelled'])->default('planned')->after('is_public');
        });

        DB::table('donation_schedules')
            ->whereNull('event_date')
            ->update([
                'event_date' => DB::raw('DATE(start_at)'),
                'start_time' => DB::raw('TIME(start_at)'),
                'end_time' => DB::raw('TIME(end_at)'),
            ]);

        Schema::table('donation_schedules', function (Blueprint $table) {
            $table->index(['event_type', 'event_date', 'status'], 'donation_schedules_type_date_status_idx');
            $table->index(['is_public', 'event_date', 'status'], 'donation_schedules_public_date_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('donation_schedules', function (Blueprint $table) {
            $table->dropIndex('donation_schedules_type_date_status_idx');
            $table->dropIndex('donation_schedules_public_date_status_idx');

            $table->dropColumn([
                'event_type',
                'event_date',
                'start_time',
                'end_time',
                'latitude',
                'longitude',
                'description',
                'contact_person',
                'status',
            ]);
        });
    }
};
