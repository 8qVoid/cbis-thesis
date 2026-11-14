<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('blood_inventory', function (Blueprint $table): void {
            $table->string('showcase_key')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('blood_inventory', function (Blueprint $table): void {
            $table->dropUnique(['showcase_key']);
            $table->dropColumn('showcase_key');
        });
    }
};
