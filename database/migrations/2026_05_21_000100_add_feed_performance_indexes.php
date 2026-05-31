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
        // Add composite index for approved posts sorted by created_at
        // Speeds up the approved() scope + ->latest() ordering
        Schema::table('posts', function (Blueprint $table) {
            $table->index(['is_approved', 'created_at'], 'posts_is_approved_created_at_index');
        });

        // Add composite index for social group post filtering
        // Speeds up whereNotNull('social_group_id') + group privacy checks
        Schema::table('posts', function (Blueprint $table) {
            $table->index(['social_group_id', 'is_approved'], 'posts_social_group_id_is_approved_index');
        });

        // Add explicit index on blocked_id for "who blocked me" lookups
        // The existing (blocker_id, blocked_id) unique composite doesn't help when blocked_id is the only filter
        Schema::table('blocks', function (Blueprint $table) {
            $table->index('blocked_id', 'blocks_blocked_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_is_approved_created_at_index');
            $table->dropIndex('posts_social_group_id_is_approved_index');
        });

        Schema::table('blocks', function (Blueprint $table) {
            $table->dropIndex('blocks_blocked_id_index');
        });
    }
};
