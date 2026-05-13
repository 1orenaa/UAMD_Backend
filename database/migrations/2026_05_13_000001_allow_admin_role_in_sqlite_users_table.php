<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement('PRAGMA foreign_keys=OFF');

        DB::statement(<<<'SQL'
            CREATE TABLE users_new (
                id integer primary key autoincrement not null,
                name varchar not null,
                email varchar not null,
                email_verified_at datetime,
                password varchar not null,
                remember_token varchar,
                created_at datetime,
                updated_at datetime,
                role varchar check ("role" in ('student', 'pedagog', 'admin')) not null default 'student'
            )
        SQL);

        DB::statement(<<<'SQL'
            INSERT INTO users_new (
                id, name, email, email_verified_at, password, remember_token, created_at, updated_at, role
            )
            SELECT id, name, email, email_verified_at, password, remember_token, created_at, updated_at, role
            FROM users
        SQL);

        DB::statement('DROP TABLE users');
        DB::statement('ALTER TABLE users_new RENAME TO users');
        DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email)');

        DB::statement('PRAGMA foreign_keys=ON');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement("DELETE FROM users WHERE role = 'admin'");
    }
};
