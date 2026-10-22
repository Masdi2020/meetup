<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = Schema::getConnection();
        $roles = ['user' => 'User', 'admin' => 'Admin', 'display' => 'Display', 'super_admin' => 'Super Admin'];

        if ($connection->table('users')->whereNotIn('role', array_keys($roles))->exists()) {
            throw new RuntimeException('Unknown user roles must be mapped before migrating.');
        }

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();
        });

        foreach ($roles as $code => $name) {
            $connection->table('roles')->insert([
                'code' => $code,
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->index()->constrained('roles');
        });

        $connection->table('users')->update([
            'role_id' => $connection->table('roles')->select('id')->whereColumn('roles.code', 'users.role'),
        ]);

        $connection->table('users')
            ->where('username', 'admin')
            ->where('role', 'admin')
            ->whereNull('deleted_at')
            ->update([
                'role_id' => $connection->table('roles')->select('id')->where('code', 'super_admin'),
            ]);

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user');
        });

        $connection = Schema::getConnection();
        $connection->table('users')->update([
            'role' => $connection->table('roles')
                ->selectRaw("CASE WHEN code = 'super_admin' THEN 'admin' ELSE code END")
                ->whereColumn('roles.id', 'users.role_id'),
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropIndex(['role_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role_id');
        });

        Schema::dropIfExists('roles');
    }
};
