<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'student_id')) {
                $table->uuid('student_id')->nullable()->after('payment_link_id')->comment('ID de l\'étudiant associé');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'student_id')) {
                $table->dropColumn('student_id');
            }
        });
    }
};
