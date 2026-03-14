<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_links', function (Blueprint $table) {
            $table->string('school_year', 20)->nullable()->after('student_id');
            $table->index('school_year');
        });

        // Backfill pour les liens existants (année académique basée sur created_at)
        DB::table('payment_links')
            ->select('id', 'created_at')
            ->whereNull('school_year')
            ->orderBy('created_at')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $dt = $row->created_at ? \Carbon\Carbon::parse($row->created_at) : now();
                    $y = $dt->month >= 9 ? $dt->year : $dt->year - 1;
                    $schoolYear = sprintf('%d-%d', $y, $y + 1);

                    DB::table('payment_links')
                        ->where('id', $row->id)
                        ->update(['school_year' => $schoolYear]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_links', function (Blueprint $table) {
            $table->dropIndex(['school_year']);
            $table->dropColumn('school_year');
        });
    }
};
