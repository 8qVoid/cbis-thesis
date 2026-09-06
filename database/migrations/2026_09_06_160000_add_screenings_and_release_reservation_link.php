<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donor_screenings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donor_id')->constrained('donors');
            $table->foreignId('reviewed_by')->constrained('users');
            $table->string('status', 30);
            $table->text('donor_message')->nullable();
            $table->date('review_on')->nullable();
            $table->timestamps();
        });
        Schema::table('blood_releases', function (Blueprint $table) {
            $table->foreignId('blood_reservation_id')->nullable()->constrained('blood_reservations');
        });
    }

    public function down(): void
    {
        Schema::table('blood_releases', fn (Blueprint $table) => $table->dropConstrainedForeignId('blood_reservation_id'));
        Schema::dropIfExists('donor_screenings');
    }
};
