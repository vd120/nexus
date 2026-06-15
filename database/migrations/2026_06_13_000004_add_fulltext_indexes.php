<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') return;

        DB::statement('ALTER TABLE posts ADD FULLTEXT INDEX ft_posts_content (content)');
        DB::statement('ALTER TABLE users ADD FULLTEXT INDEX ft_users_search (name, username)');
        DB::statement('ALTER TABLE social_groups ADD FULLTEXT INDEX ft_groups_search (name, description)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') return;

        DB::statement('ALTER TABLE posts DROP INDEX ft_posts_content');
        DB::statement('ALTER TABLE users DROP INDEX ft_users_search');
        DB::statement('ALTER TABLE social_groups DROP INDEX ft_groups_search');
    }
};
