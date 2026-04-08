<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('blood_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->foreignId('donation_record_id')->nullable()->constrained('donation_records')->nullOnDelete();
            $table->enum('blood_type', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']);
            $table->unsignedInteger('units_available')->default(0);
            $table->date('expiration_date');
            $table->enum('status', ['active', 'low_stock', 'expired'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['facility_id', 'blood_type', 'status']);
            $table->index(['facility_id', 'expiration_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_inventory');
    }
};
