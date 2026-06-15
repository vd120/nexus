<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all users with usernames that contain hyphens or other special chars
        // REGEXP is MySQL-only; on SQLite we fetch all and filter in PHP.
        if (DB::getDriverName() === 'mysql') {
            $users = DB::table('users')->where('username', 'REGEXP', '[^a-zA-Z0-9]')->get();
        } else {
            $users = DB::table('users')->get()->filter(fn($u) => preg_match('/[^a-zA-Z0-9]/', $u->username));
        }

        foreach ($users as $user) {
            // Generate new username by removing all non-alphanumeric characters
            $newUsername = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $user->username));
            
            // If username becomes empty, use 'user' + id
            if (empty($newUsername)) {
                $newUsername = 'user' . $user->id;
            }
            
            $originalUsername = $newUsername;
            $counter = 1;

            // Ensure uniqueness
            while (DB::table('users')
                ->where('username', $newUsername)
                ->where('id', '!=', $user->id)
                ->exists()
            ) {
                $newUsername = $originalUsername . $counter;
                $counter++;
            }

            // Update user with new username
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'username' => $newUsername,
                    'username_changed_at' => now() // Record the change time
                ]);

            echo "Updated user ID {$user->id}: '{$user->username}' -> '{$newUsername}'\n";
        }

        echo "Username update complete!\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reverse automatically as we don't store the old usernames
        // This is intentional - the new format is the standard going forward
    }
};
