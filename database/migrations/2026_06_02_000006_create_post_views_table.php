<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('viewed_at');

            // One view per authenticated user per post
            $table->unique(['post_id', 'user_id']);
            $table->index(['post_id', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_views');
    }
};
