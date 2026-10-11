<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('refills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('refill_spot_id')->constrained();
            $table->uuid('client_id'); // made by the phone; same id = same refill
            $table->decimal('latitude', 9, 6);
            $table->decimal('longitude', 9, 6);
            $table->unsignedInteger('distance_m')->nullable(); // filled in by the checks
            $table->string('auto_status', 20)->default('pending'); // what the checks decided
            $table->string('auto_reason', 30)->nullable(); // too_far, daily_limit, duplicate
            $table->string('status', 20)->default('pending'); // final; staff can override later
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->text('review_reason')->nullable();
            $table->timestamp('logged_at'); // server time, never the phone's clock
            $table->timestamps();

            $table->unique(['user_id', 'client_id']); // a retry can't make a second row
            $table->index(['user_id', 'logged_at']); // daily limit and history
            $table->index(['refill_spot_id', 'logged_at']); // repeat check
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refills');
    }
};
