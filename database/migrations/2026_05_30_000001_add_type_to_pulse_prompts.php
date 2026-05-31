<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pulse_prompts', function (Blueprint $t) {
            $t->enum('type', ['daily', 'memory'])->default('daily')->after('question_ar');
        });
    }

    public function down(): void
    {
        Schema::table('pulse_prompts', function (Blueprint $t) {
            $t->dropColumn('type');
        });
    }
};
