<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('blood_inventory', function (Blueprint $table): void {
            $table->enum('component', ['whole_blood', 'packed_red_blood_cells', 'platelet_concentrate', 'fresh_frozen_plasma'])
                ->default('whole_blood')->after('blood_type');
            $table->index(['facility_id', 'blood_type', 'component'], 'inventory_facility_blood_component_idx');
        });

        Schema::table('donation_schedules', function (Blueprint $table): void {
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('is_public');
            $table->foreignId('reviewed_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_notes')->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('donation_schedules', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['approval_status', 'reviewed_at', 'review_notes']);
        });
        Schema::table('blood_inventory', function (Blueprint $table): void {
            $table->dropIndex('inventory_facility_blood_component_idx');
            $table->dropColumn('component');
        });
    }
};
