<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_username_unique');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('active_username')
                ->nullable()
                ->storedAs('case when `deleted_at` is null then `username` else null end')
                ->unique();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_active_username_unique');
            $table->dropColumn('active_username');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');
        });
    }
};
