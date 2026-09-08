<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('username')->unique();
            $table->string('password');
            $table->boolean('force_change_password')->default(false);
            $table->string('name');
            $table->string('role')->default('user');

            $table->rememberToken();
            $table->timestamps();

            $table->softDeletes();

            $table->string('active_username')
                ->nullable()
                ->storedAs(
                    'case when `deleted_at` is null then `username` else null end'
                )
                ->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
