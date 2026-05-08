<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_group_id')->constrained('social_groups')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('role', ['admin', 'moderator', 'member'])->default('member');
            $table->enum('status', ['pending', 'approved', 'rejected', 'banned'])->default('approved');
            $table->enum('notification_preference', ['all', 'highlights', 'none'])->default('all');
            $table->boolean('is_anonymous_default')->default(false);
            $table->string('anonymous_username')->nullable();
            $table->timestamp('muted_until')->nullable();
            $table->timestamps();

            $table->unique(['social_group_id', 'user_id']);
            $table->unique(['social_group_id', 'anonymous_username']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_group_members');
    }
};
