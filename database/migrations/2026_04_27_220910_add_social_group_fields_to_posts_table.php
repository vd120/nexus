<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('social_group_id')->nullable()->constrained('social_groups')->onDelete('cascade');
            $table->foreignId('social_group_topic_id')->nullable()->constrained('social_group_topics')->onDelete('set null');
            $table->boolean('is_anonymous')->default(false);
            $table->boolean('is_approved')->default(true);
            $table->boolean('is_comments_disabled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['social_group_id']);
            $table->dropForeign(['social_group_topic_id']);
            $table->dropColumn(['social_group_id', 'social_group_topic_id', 'is_anonymous', 'is_approved', 'is_comments_disabled']);
        });
    }
};
