<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('theme_name', 32)->default('default-violet')->after('remember_token');
            $table->string('theme_mode', 10)->default('dark')->after('theme_name');
            $table->boolean('use_system_mode')->default(false)->after('theme_mode');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['theme_name', 'theme_mode', 'use_system_mode']);
        });
    }
};
