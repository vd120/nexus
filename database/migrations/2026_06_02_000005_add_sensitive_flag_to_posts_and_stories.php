<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->boolean('is_sensitive')->default(false)->after('is_comments_disabled');
        });

        Schema::table('stories', function (Blueprint $table) {
            $table->boolean('is_sensitive')->default(false)->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('is_sensitive');
        });
        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn('is_sensitive');
        });
    }
};
