<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE facilities MODIFY contact_number VARCHAR(120) NULL');
        DB::statement('ALTER TABLE facility_applications MODIFY contact_number VARCHAR(120) NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE facilities MODIFY contact_number VARCHAR(30) NULL');
        DB::statement('ALTER TABLE facility_applications MODIFY contact_number VARCHAR(30) NOT NULL');
    }
};
