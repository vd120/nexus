<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // No constrained() — we manually null on chapter delete via observer/controller.
            $table->unsignedBigInteger('life_chapter_id')->nullable()->after('user_id');
            $table->index('life_chapter_id');
        });

        Schema::table('pulse_answers', function (Blueprint $table) {
            $table->unsignedBigInteger('life_chapter_id')->nullable()->after('user_id');
            $table->index('life_chapter_id');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['life_chapter_id']);
            $table->dropColumn('life_chapter_id');
        });

        Schema::table('pulse_answers', function (Blueprint $table) {
            $table->dropIndex(['life_chapter_id']);
            $table->dropColumn('life_chapter_id');
        });
    }
};
