<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE messages MODIFY COLUMN type ENUM('text', 'image', 'video', 'file', 'system', 'group_invite') DEFAULT 'text'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE messages MODIFY COLUMN type ENUM('text', 'image', 'file') DEFAULT 'text'");
        }
    }
};