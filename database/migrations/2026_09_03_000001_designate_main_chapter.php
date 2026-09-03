<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->boolean('is_main_chapter')->default(false)->index();
        });
        // Designate the established Bacolod seed record; never move existing stock.
        DB::table('facilities')->where('code', 'FAC-001')
            ->where('name', 'PHILIPPINE RED CROSS Bacolod City Chapter')
            ->update(['is_main_chapter' => true]);
    }

    public function down(): void
    {
        Schema::table('facilities', fn (Blueprint $table) => $table->dropColumn('is_main_chapter'));
    }
};
