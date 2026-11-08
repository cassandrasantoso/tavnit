<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How to read a shop's name: a kana reading (turned into Latin letters
     * for staff who don't read Japanese) and an optional official English name.
     * The name itself is never translated.
     */
    public function up(): void
    {
        Schema::table('partner_applications', function (Blueprint $table) {
            $table->string('store_name_kana', 100)->nullable();
            $table->string('store_name_en', 100)->nullable();
        });

        Schema::table('refill_spots', function (Blueprint $table) {
            $table->string('name_kana', 100)->nullable();
            $table->string('name_en', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('partner_applications', function (Blueprint $table) {
            $table->dropColumn(['store_name_kana', 'store_name_en']);
        });

        Schema::table('refill_spots', function (Blueprint $table) {
            $table->dropColumn(['name_kana', 'name_en']);
        });
    }
};
