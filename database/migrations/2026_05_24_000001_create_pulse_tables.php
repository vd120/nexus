<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pulse_prompts', function (Blueprint $table) {
            $table->id();
            $table->text('question_en');
            $table->text('question_ar');
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            // Used by the daily rotation job to mark which prompts have been
            // featured so we cycle through the full library before repeating.
            $table->timestamp('last_used_at')->nullable()->index();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('pulse_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prompt_id')->constrained('pulse_prompts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('content');
            $table->boolean('is_anonymous')->default(false);
            $table->timestamps();

            // Most queries: "show today's answers for the active prompt"
            $table->index(['prompt_id', 'created_at']);
            // Anti-spam: one answer per user per prompt
            $table->unique(['prompt_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pulse_answers');
        Schema::dropIfExists('pulse_prompts');
    }
};
