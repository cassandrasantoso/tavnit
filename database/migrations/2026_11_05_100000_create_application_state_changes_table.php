<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row every time an application moves to another state: who, from, to, why, when.
        // Rows are only ever added, never changed, so there is no updated_at.
        Schema::create('application_state_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_state', 20);
            $table->string('to_state', 20);
            $table->string('reason', 40)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['partner_application_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_state_changes');
    }
};
