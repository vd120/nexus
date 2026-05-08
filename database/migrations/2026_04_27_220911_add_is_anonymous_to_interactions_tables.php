<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->boolean('is_anonymous')->default(false);
        });

        Schema::table('post_reactions', function (Blueprint $table) {
            $table->boolean('is_anonymous')->default(false);
        });

        Schema::table('comment_likes', function (Blueprint $table) {
            $table->boolean('is_anonymous')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn('is_anonymous');
        });

        Schema::table('post_reactions', function (Blueprint $table) {
            $table->dropColumn('is_anonymous');
        });

        Schema::table('comment_likes', function (Blueprint $table) {
            $table->dropColumn('is_anonymous');
        });
    }
};
