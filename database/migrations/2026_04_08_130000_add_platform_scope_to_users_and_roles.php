<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE users MODIFY scope ENUM('platform', 'institution', 'annexe') NOT NULL DEFAULT 'annexe'");
            DB::statement("ALTER TABLE roles MODIFY scope ENUM('platform', 'institution', 'annexe') NOT NULL");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE users MODIFY scope ENUM('institution', 'annexe') NOT NULL DEFAULT 'annexe'");
            DB::statement("ALTER TABLE roles MODIFY scope ENUM('institution', 'annexe') NOT NULL");
        }
    }
};