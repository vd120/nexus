<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->boolean('show_online_status')->default(true)->after('is_private');
            $table->boolean('show_read_receipts')->default(true)->after('show_online_status');
            $table->boolean('show_sensitive_content')->default(false)->after('show_read_receipts');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['show_online_status', 'show_read_receipts', 'show_sensitive_content']);
        });
    }
};
