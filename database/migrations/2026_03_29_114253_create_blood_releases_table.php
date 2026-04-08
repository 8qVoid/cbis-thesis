<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('blood_releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->foreignId('blood_inventory_id')->constrained('blood_inventory')->cascadeOnDelete();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('patient_name')->nullable();
            $table->string('requesting_unit')->nullable();
            $table->dateTime('released_at');
            $table->unsignedInteger('units_released');
            $table->text('purpose')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['facility_id', 'released_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_releases');
    }
};
