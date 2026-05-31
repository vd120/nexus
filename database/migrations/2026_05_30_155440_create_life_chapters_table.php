<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('life_chapters', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('title', 120);
            $t->string('emoji', 16)->nullable();
            $t->date('starts_on')->nullable();
            $t->date('ends_on')->nullable(); // null = ongoing
            $t->text('description')->nullable();
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->index(['user_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('life_chapters');
    }
};
