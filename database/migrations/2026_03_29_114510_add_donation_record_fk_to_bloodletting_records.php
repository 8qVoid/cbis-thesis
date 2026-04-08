<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bloodletting_records', function (Blueprint $table) {
            $table->foreign('donation_record_id')
                ->references('id')
                ->on('donation_records')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bloodletting_records', function (Blueprint $table) {
            $table->dropForeign(['donation_record_id']);
        });
    }
};
