<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_group_member_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_group_id')->constrained('social_groups')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('badge_id')->constrained('social_group_badges')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['social_group_id', 'user_id', 'badge_id'], 'sg_member_badges_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_group_member_badges');
    }
};
