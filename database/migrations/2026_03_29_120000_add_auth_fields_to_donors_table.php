<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('donors', function (Blueprint $table) {
            $table->string('password')->nullable()->after('email');
            $table->rememberToken();
            $table->boolean('is_online_registered')->default(false)->after('is_eligible');
            $table->unique('email');
        });
    }

    public function down(): void
    {
        Schema::table('donors', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropColumn(['password', 'remember_token', 'is_online_registered']);
        });
    }
};
