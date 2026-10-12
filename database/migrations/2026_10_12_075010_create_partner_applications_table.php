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
        Schema::create('partner_applications', function (Blueprint $table) {
            $table->id();
            // What the shop sends in.
            $table->string('store_name', 100);
            $table->string('store_type', 30);
            $table->string('address', 200);
            $table->text('description_ja')->nullable();
            $table->text('hours_note_ja')->nullable();
            $table->text('message_ja')->nullable();
            $table->date('opening_date')->nullable();
            $table->string('contact_email');
            $table->decimal('latitude', 9, 6);
            $table->decimal('longitude', 9, 6);
            // What staff control.
            $table->string('state', 20)->default('submitted');
            $table->foreignId('claimed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('claimed_at')->nullable();
            $table->date('publish_on')->nullable();
            $table->foreignId('refill_spot_id')->nullable()->constrained()->nullOnDelete();
            // Goes up by one on every change, so two reviewers can't overwrite each other.
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            // The queue lists applications by state, oldest first.
            $table->index(['state', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_applications');
    }
};
