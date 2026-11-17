<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('donation_records', fn (Blueprint $table) => $table->time('expiration_time')->nullable());
        Schema::table('blood_inventory', fn (Blueprint $table) => $table->time('expiration_time')->nullable());
    }

    public function down(): void
    {
        Schema::table('blood_inventory', fn (Blueprint $table) => $table->dropColumn('expiration_time'));
        Schema::table('donation_records', fn (Blueprint $table) => $table->dropColumn('expiration_time'));
    }
};
