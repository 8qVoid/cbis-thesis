<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('donation_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('donation_schedules', 'photo_path')) {
                $table->string('photo_path')->nullable()->after('description');
            }
        });

        Schema::table('blood_bank_locations', function (Blueprint $table) {
            if (! Schema::hasColumn('blood_bank_locations', 'photo_path')) {
                $table->string('photo_path')->nullable()->after('contact_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('donation_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('donation_schedules', 'photo_path')) {
                $table->dropColumn('photo_path');
            }
        });

        Schema::table('blood_bank_locations', function (Blueprint $table) {
            if (Schema::hasColumn('blood_bank_locations', 'photo_path')) {
                $table->dropColumn('photo_path');
            }
        });
    }
};
