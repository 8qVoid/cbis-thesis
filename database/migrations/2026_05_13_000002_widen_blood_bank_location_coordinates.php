<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blood_bank_locations', function (Blueprint $table) {
            $table->decimal('latitude', 18, 15)->change();
            $table->decimal('longitude', 18, 15)->change();
        });
    }

    public function down(): void
    {
        Schema::table('blood_bank_locations', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->change();
            $table->decimal('longitude', 10, 7)->change();
        });
    }
};
