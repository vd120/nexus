<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedInteger('cached_likes_count')->default(0)->after('is_approved');
            $table->unsignedInteger('cached_comments_count')->default(0)->after('cached_likes_count');
            $table->unsignedInteger('cached_reactions_count')->default(0)->after('cached_comments_count');
        });

        // Backfill existing rows
        DB::statement('UPDATE posts SET
            cached_likes_count     = (SELECT COUNT(*) FROM likes      WHERE likes.post_id          = posts.id),
            cached_comments_count  = (SELECT COUNT(*) FROM comments   WHERE comments.post_id        = posts.id),
            cached_reactions_count = (SELECT COUNT(*) FROM post_reactions WHERE post_reactions.post_id = posts.id)
        ');
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['cached_likes_count', 'cached_comments_count', 'cached_reactions_count']);
        });
    }
};
