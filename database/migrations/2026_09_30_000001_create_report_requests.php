<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('report_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('facility_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('requester_name');
            $table->string('report_type', 32);
            $table->string('status', 20)->default('pending');
            $table->string('pending_key', 100)->nullable()->unique();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reviewer_name')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['requested_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_requests');
    }
};
