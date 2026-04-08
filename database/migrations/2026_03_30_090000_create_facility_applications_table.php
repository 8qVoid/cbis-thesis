<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('facility_applications', function (Blueprint $table) {
            $table->id();
            $table->string('organization_name');
            $table->enum('facility_type', ['blood_bank', 'hospital']);
            $table->string('contact_person');
            $table->string('contact_number', 30);
            $table->string('email');
            $table->text('address');
            $table->string('doh_accreditation_number')->nullable();
            $table->string('legitimacy_proof_path');
            $table->string('doh_accreditation_proof_path');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('review_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('facility_id')->nullable()->constrained('facilities')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index('facility_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_applications');
    }
};
