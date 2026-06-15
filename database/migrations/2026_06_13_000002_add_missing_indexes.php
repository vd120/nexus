<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->index('deleted_at', 'posts_deleted_at_index');
            $table->index('social_group_id', 'posts_social_group_id_index');
        });

        Schema::table('social_group_members', function (Blueprint $table) {
            $table->index('user_id', 'sgm_user_id_index');
            $table->index('status', 'sgm_status_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index('type', 'notifications_type_index');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->index('parent_id', 'comments_parent_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_deleted_at_index');
            $table->dropIndex('posts_social_group_id_index');
        });

        Schema::table('social_group_members', function (Blueprint $table) {
            $table->dropIndex('sgm_user_id_index');
            $table->dropIndex('sgm_status_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_type_index');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_parent_id_index');
        });
    }
};
