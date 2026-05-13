<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_applications', function (Blueprint $table) {
            $table->string('contact_person')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('facility_applications')
            ->whereNull('contact_person')
            ->update(['contact_person' => '']);

        Schema::table('facility_applications', function (Blueprint $table) {
            $table->string('contact_person')->nullable(false)->change();
        });
    }
};
