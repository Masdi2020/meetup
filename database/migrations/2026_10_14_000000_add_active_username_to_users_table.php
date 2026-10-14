<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        $expression = 'CASE WHEN deleted_at IS NULL THEN username ELSE NULL END';

        if ($driver === 'sqlsrv') {
            $connection->statement("ALTER TABLE users ADD active_username AS ($expression) PERSISTED");
            // Filter on the source column; SQL Server cannot filter on a computed column.
            $connection->statement('CREATE UNIQUE INDEX users_active_username_unique ON users (active_username) WHERE deleted_at IS NULL');
        } else {
            Schema::table('users', function (Blueprint $table) use ($driver, $expression) {
                $column = $table->string('active_username')->nullable();

                if ($driver === 'pgsql') {
                    $column->storedAs($expression);
                } else {
                    $column->virtualAs($expression);
                }

                $table->unique('active_username');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_username_unique');
        });
    }

    public function down(): void
    {
        // Restore the old constraint first. Rollback fails safely if usernames
        // have been reused, without deleting or renaming historical users.
        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');
        });

        $connection = Schema::getConnection();

        if ($connection->getDriverName() === 'sqlsrv') {
            $connection->statement('DROP INDEX users_active_username_unique ON users');
            $connection->statement('ALTER TABLE users DROP COLUMN active_username');
        } elseif ($connection->getDriverName() === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_active_username_unique');
            });
            $connection->statement('ALTER TABLE users DROP COLUMN active_username');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_active_username_unique');
                $table->dropColumn('active_username');
            });
        }
    }
};
