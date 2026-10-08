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
        Schema::create('refill_spots', function (Blueprint $table) {
          $table->id();
          $table->string('name', 100);
          $table->text('description_ja')->nullable();
          $table->decimal('latitude', 9, 6);
          $table->decimal('longitude', 9, 6);
          $table->boolean('is_active')->default(true);
          $table->timestamps();
          $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refill_spots');
    }
};
