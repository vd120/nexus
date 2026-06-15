<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add unique composite index on (user_id, endpoint) to prevent duplicate subscriptions.
        // Endpoint is TEXT; MySQL requires a prefix length for TEXT unique indexes.
        try {
            DB::statement('ALTER TABLE push_subscriptions ADD UNIQUE INDEX push_unique_user_endpoint (user_id, endpoint(500))');
        } catch (\Exception $e) {
            // Index already exists — safe to ignore
        }
    }

    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE push_subscriptions DROP INDEX push_unique_user_endpoint');
        } catch (\Exception $e) {
            // Index doesn't exist — safe to ignore
        }
    }
};
