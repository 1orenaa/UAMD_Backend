<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ekzekutohet vetem ne SQL Server — SQLite (qe perdoret ne teste) nuk
        // permban sys.check_constraints dhe T-SQL sintaksen e meposhtme.
        if (DB::getDriverName() !== 'sqlsrv') {
            return;
        }

        DB::statement("
            DECLARE @constraint NVARCHAR(200);
            SELECT @constraint = name
            FROM sys.check_constraints
            WHERE parent_object_id = OBJECT_ID('users')
              AND name LIKE '%role%';

            IF @constraint IS NOT NULL
                EXEC('ALTER TABLE users DROP CONSTRAINT [' + @constraint + ']');

            ALTER TABLE users
                ADD CONSTRAINT chk_users_role
                CHECK (role IN ('student', 'pedagog', 'admin'));
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            return;
        }

        DB::statement("
            IF OBJECT_ID('chk_users_role') IS NOT NULL
                ALTER TABLE users DROP CONSTRAINT chk_users_role;

            ALTER TABLE users
                ADD CONSTRAINT chk_users_role_old
                CHECK (role IN ('student', 'pedagog'));
        ");
    }
};
