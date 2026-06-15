<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->boolean('show_location')->default(true)->after('location');
            $table->boolean('show_occupation')->default(true)->after('occupation');
            $table->boolean('show_gender')->default(true)->after('gender');
            $table->boolean('show_birth_date')->default(true)->after('birth_date');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['show_location', 'show_occupation', 'show_gender', 'show_birth_date']);
        });
    }
};
