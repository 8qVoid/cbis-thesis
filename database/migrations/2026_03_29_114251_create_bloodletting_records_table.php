<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bloodletting_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->unsignedBigInteger('donation_record_id');
            $table->foreignId('medical_technologist_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('bloodletting_at');
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('findings')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['facility_id', 'bloodletting_at']);
            $table->index('donation_record_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bloodletting_records');
    }
};
