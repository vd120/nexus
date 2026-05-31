<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pulse_answers', function (Blueprint $t) {
            $t->enum('visibility', ['self', 'followers', 'public'])->default('public')->after('is_anonymous');
        });
    }

    public function down(): void
    {
        Schema::table('pulse_answers', function (Blueprint $t) {
            $t->dropColumn('visibility');
        });
    }
};
