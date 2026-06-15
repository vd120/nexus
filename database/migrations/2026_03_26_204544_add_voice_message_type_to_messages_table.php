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
        Schema::table('messages', function (Blueprint $table) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE messages MODIFY COLUMN type ENUM('text', 'image', 'video', 'audio', 'document', 'gif', 'sticker', 'story_reply', 'group_invite', 'voice') DEFAULT 'text'");
            }
            $table->integer('duration')->nullable()->after('type');
            $table->json('waveform_peaks')->nullable()->after('duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['duration', 'waveform_peaks']);
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE messages MODIFY COLUMN type ENUM('text', 'image', 'video', 'audio', 'document', 'gif', 'sticker', 'story_reply', 'group_invite') DEFAULT 'text'");
            }
        });
    }
};
