<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('blood_reservations', function (Blueprint $table): void {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('patient_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained()->restrictOnDelete();
            $table->enum('blood_type', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']);
            $table->enum('component', ['whole_blood', 'packed_red_blood_cells', 'platelet_concentrate', 'fresh_frozen_plasma']);
            $table->unsignedInteger('units_requested');
            $table->date('needed_on');
            $table->text('clinical_purpose')->nullable();
            $table->enum('status', ['submitted', 'under_review', 'approved', 'rejected', 'fulfilled', 'cancelled'])->default('submitted');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->index(['facility_id', 'status']);
            $table->index(['patient_user_id', 'status']);
        });

        Schema::create('blood_reservation_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('blood_reservation_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['blood_request', 'referral_letter', 'identification', 'prescription']);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_reservation_documents');
        Schema::dropIfExists('blood_reservations');
    }
};
