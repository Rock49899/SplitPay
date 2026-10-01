<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les années scolaires deviennent propres à chaque institution :
 * la clôture d'année d'une institution ne doit plus impacter les autres.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_years', function (Blueprint $table) {
            $table->foreignUuid('institution_id')->nullable()->after('id')
                ->constrained('institutions')->cascadeOnDelete();
        });

        Schema::table('school_years', function (Blueprint $table) {
            $table->dropUnique('school_years_year_unique');
        });

        // Dupliquer le calendrier global existant pour chaque institution
        $globalYears = DB::table('school_years')->whereNull('institution_id')->get();
        $institutionIds = DB::table('institutions')->pluck('id');

        foreach ($institutionIds as $institutionId) {
            foreach ($globalYears as $year) {
                DB::table('school_years')->insert([
                    'institution_id' => $institutionId,
                    'year' => $year->year,
                    'status' => $year->status,
                    'opened_at' => $year->opened_at,
                    'closed_at' => $year->closed_at,
                    'promoted_to_year' => $year->promoted_to_year,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Plus aucune année sans institution : chaque institution reçoit son calendrier
        // (copie ci-dessus, ou SchoolYear::ensureForInstitution() à sa création).
        DB::table('school_years')->whereNull('institution_id')->delete();

        Schema::table('school_years', function (Blueprint $table) {
            $table->unique(['institution_id', 'year'], 'school_years_institution_year_unique');
        });
    }

    public function down(): void
    {
        Schema::table('school_years', function (Blueprint $table) {
            $table->dropUnique('school_years_institution_year_unique');
        });

        // Revenir à un calendrier global : une ligne par année (l'active en priorité)
        $keepIds = DB::table('school_years')
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 WHEN status = 'closed' THEN 1 ELSE 2 END")
            ->orderBy('id')
            ->get(['id', 'year'])
            ->unique('year')
            ->pluck('id');

        DB::table('school_years')->whereNotIn('id', $keepIds)->delete();

        Schema::table('school_years', function (Blueprint $table) {
            $table->dropConstrainedForeignId('institution_id');
            $table->unique('year');
        });
    }
};
