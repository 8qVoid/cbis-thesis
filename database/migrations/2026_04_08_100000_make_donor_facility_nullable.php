<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('donors', function (Blueprint $table) {
            $table->dropForeign(['facility_id']);
        });

        Schema::table('donors', function (Blueprint $table) {
            $table->unsignedBigInteger('facility_id')->nullable()->change();
        });

        Schema::table('donors', function (Blueprint $table) {
            $table->foreign('facility_id')->references('id')->on('facilities')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('donors', function (Blueprint $table) {
            $table->dropForeign(['facility_id']);
        });

        $defaultFacilityId = DB::table('facilities')->min('id');
        if ($defaultFacilityId === null) {
            throw new \RuntimeException('Cannot rollback donor facility nullability without at least one facility record.');
        }

        DB::statement('UPDATE donors SET facility_id = '.$defaultFacilityId.' WHERE facility_id IS NULL');
        Schema::table('donors', function (Blueprint $table) {
            $table->unsignedBigInteger('facility_id')->nullable(false)->change();
        });

        Schema::table('donors', function (Blueprint $table) {
            $table->foreign('facility_id')->references('id')->on('facilities')->cascadeOnDelete();
        });
    }
};
