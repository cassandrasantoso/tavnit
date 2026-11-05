<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Saved machine translations of free-text fields, one row per
     * record, field and language.
     */
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->morphs('translatable'); // which record: type + id
            $table->string('field', 40);    // e.g. description_ja
            $table->string('locale', 10);   // the language it was translated into
            $table->text('text');
            $table->char('source_hash', 64); // sha256 of the original, to spot edits
            $table->string('provider', 40);  // e.g. google-v3+glossary
            $table->timestamp('translated_at');
            $table->timestamps();

            $table->unique(['translatable_type', 'translatable_id', 'field', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
