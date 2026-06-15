<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('messages')->where('type', 'group_invite')->update(['type' => 'system']);

        // MODIFY COLUMN is MySQL-only; SQLite stores any string so no DDL needed.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE messages MODIFY COLUMN type ENUM('text', 'image', 'video', 'file', 'system', 'group_invite') DEFAULT 'text'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE messages MODIFY COLUMN type ENUM('text', 'image', 'video', 'file') DEFAULT 'text'");
        }
    }
};