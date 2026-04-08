<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('blood_bank_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('address');
            $table->string('contact_number', 30)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('facility_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_bank_locations');
    }
};
