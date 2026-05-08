<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('cover_photo')->nullable();
            $table->string('avatar')->nullable();
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
            $table->enum('privacy_level', ['public', 'private'])->default('public');
            $table->boolean('is_discoverable')->default(true);
            $table->enum('posting_permission', ['anyone', 'admins_only'])->default('anyone');
            $table->integer('new_member_restriction_days')->default(0);
            $table->boolean('require_post_approval')->default(false);
            $table->boolean('allow_anonymous_posts')->default(false);
            $table->boolean('is_paused')->default(false);
            $table->text('welcome_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_groups');
    }
};
