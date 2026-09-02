<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('facilities', fn (Blueprint $table) => $table->string('contact_number', 120)->nullable()->change());
        Schema::table('facility_applications', fn (Blueprint $table) => $table->string('contact_number', 120)->change());
    }

    public function down(): void
    {
        Schema::table('facilities', fn (Blueprint $table) => $table->string('contact_number', 30)->nullable()->change());
        Schema::table('facility_applications', fn (Blueprint $table) => $table->string('contact_number', 30)->change());
    }
};
