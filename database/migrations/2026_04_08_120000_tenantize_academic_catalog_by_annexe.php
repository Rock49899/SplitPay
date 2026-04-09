<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('study_levels', 'annexe_id')) {
            Schema::table('study_levels', function (Blueprint $table) {
                $table->foreignUuid('annexe_id')->nullable()->after('id')->constrained('annexes')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('specializations', 'annexe_id')) {
            Schema::table('specializations', function (Blueprint $table) {
                $table->foreignUuid('annexe_id')->nullable()->after('id')->constrained('annexes')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('level_fees', 'annexe_id')) {
            Schema::table('level_fees', function (Blueprint $table) {
                $table->foreignUuid('annexe_id')->nullable()->after('id')->constrained('annexes')->nullOnDelete();
            });
        }

        try {
            Schema::table('study_levels', function (Blueprint $table) {
                $table->index(['annexe_id', 'code'], 'idx_study_levels_annexe_code');
            });
        } catch (\Throwable $e) {
            // index déjà présent ou colonne indisponible: ignorer
        }

        try {
            Schema::table('specializations', function (Blueprint $table) {
                $table->index(['annexe_id', 'code'], 'idx_specializations_annexe_code');
            });
        } catch (\Throwable $e) {
            // index déjà présent ou colonne indisponible: ignorer
        }

        try {
            Schema::table('level_fees', function (Blueprint $table) {
                $table->index(['annexe_id', 'school_year'], 'idx_level_fees_annexe_school_year');
            });
        } catch (\Throwable $e) {
            // index déjà présent ou colonne indisponible: ignorer
        }

        try {
            Schema::table('level_fees', function (Blueprint $table) {
                $table->dropUnique('uq_level_fees');
            });
        } catch (\Throwable $e) {
            // unique absent: ignorer
        }

        try {
            Schema::table('level_fees', function (Blueprint $table) {
                $table->unique([
                    'annexe_id',
                    'study_level_id',
                    'specialization_id',
                    'school_year',
                ], 'uq_level_fees_annexe');
            });
        } catch (\Throwable $e) {
            // unique déjà présent: ignorer
        }

        // Si des lignes tenantisées existent déjà, on considère la migration appliquée
        $alreadyTenantized = DB::table('study_levels')->whereNotNull('annexe_id')->exists()
            || DB::table('specializations')->whereNotNull('annexe_id')->exists()
            || DB::table('level_fees')->whereNotNull('annexe_id')->exists();

        if ($alreadyTenantized) {
            return;
        }

        $sourceStudyLevels = DB::table('study_levels')->whereNull('annexe_id')->get();
        $sourceSpecializations = DB::table('specializations')->whereNull('annexe_id')->get();
        $sourceLevelFees = DB::table('level_fees')->whereNull('annexe_id')->get();

        if ($sourceStudyLevels->isEmpty() && $sourceSpecializations->isEmpty() && $sourceLevelFees->isEmpty()) {
            return;
        }

        $institutions = DB::table('annexes')
            ->select('institution_id')
            ->distinct()
            ->pluck('institution_id');

        if ($institutions->isEmpty()) {
            return;
        }

        $principalAnnexeByInstitution = DB::table('user_annexes as ua')
            ->join('annexes as a', 'a.id', '=', 'ua.annexe_id')
            ->where('ua.is_principal', true)
            ->select('a.institution_id', DB::raw('MIN(a.id) as annexe_id'))
            ->groupBy('a.institution_id')
            ->pluck('annexe_id', 'institution_id');

        $fallbackAnnexeByInstitution = DB::table('annexes')
            ->select('institution_id', DB::raw('MIN(id) as annexe_id'))
            ->groupBy('institution_id')
            ->pluck('annexe_id', 'institution_id');

        $targetAnnexeIds = [];
        foreach ($institutions as $institutionId) {
            $annexeId = $principalAnnexeByInstitution[$institutionId] ?? $fallbackAnnexeByInstitution[$institutionId] ?? null;
            if ($annexeId) {
                $targetAnnexeIds[] = $annexeId;
            }
        }

        $targetAnnexeIds = array_values(array_unique($targetAnnexeIds));

        if (empty($targetAnnexeIds)) {
            return;
        }

        DB::transaction(function () use ($targetAnnexeIds, $sourceStudyLevels, $sourceSpecializations, $sourceLevelFees) {
            $levelFeeMapByAnnexe = [];

            foreach ($targetAnnexeIds as $annexeId) {
                $studyLevelMap = [];
                foreach ($sourceStudyLevels as $sourceStudyLevel) {
                    $newId = DB::table('study_levels')->insertGetId([
                        'annexe_id' => $annexeId,
                        'code' => $sourceStudyLevel->code,
                        'order' => $sourceStudyLevel->order,
                        'label' => $sourceStudyLevel->label,
                        'description' => $sourceStudyLevel->description,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $studyLevelMap[$sourceStudyLevel->id] = $newId;
                }

                $specializationMap = [];
                foreach ($sourceSpecializations as $sourceSpecialization) {
                    $newId = DB::table('specializations')->insertGetId([
                        'annexe_id' => $annexeId,
                        'code' => $sourceSpecialization->code,
                        'label' => $sourceSpecialization->label,
                        'description' => $sourceSpecialization->description,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $specializationMap[$sourceSpecialization->id] = $newId;
                }

                $levelFeeMap = [];
                foreach ($sourceLevelFees as $sourceLevelFee) {
                    $newLevelFeeId = DB::table('level_fees')->insertGetId([
                        'annexe_id' => $annexeId,
                        'study_level_id' => $studyLevelMap[$sourceLevelFee->study_level_id] ?? $sourceLevelFee->study_level_id,
                        'specialization_id' => $sourceLevelFee->specialization_id
                            ? ($specializationMap[$sourceLevelFee->specialization_id] ?? null)
                            : null,
                        'school_year' => $sourceLevelFee->school_year,
                        'tuition_amount' => $sourceLevelFee->tuition_amount,
                        'notes' => $sourceLevelFee->notes,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $levelFeeMap[$sourceLevelFee->id] = $newLevelFeeId;
                }

                $levelFeeMapByAnnexe[$annexeId] = $levelFeeMap;
            }

            foreach ($levelFeeMapByAnnexe as $annexeId => $levelFeeMap) {
                foreach ($levelFeeMap as $oldFeeId => $newFeeId) {
                    $enrollmentIds = DB::table('enrollments as e')
                        ->join('students as s', 's.id', '=', 'e.student_id')
                        ->where('s.annexe_id', $annexeId)
                        ->where('e.level_fee_id', $oldFeeId)
                        ->pluck('e.id');

                    if ($enrollmentIds->isNotEmpty()) {
                        DB::table('enrollments')
                            ->whereIn('id', $enrollmentIds)
                            ->update(['level_fee_id' => $newFeeId]);
                    }
                }
            }
        });
    }

    public function down(): void
    {
        try {
            Schema::table('level_fees', function (Blueprint $table) {
                $table->dropUnique('uq_level_fees_annexe');
            });
        } catch (\Throwable $e) {
            // unique absent: ignorer
        }

        try {
            Schema::table('level_fees', function (Blueprint $table) {
                $table->unique(['study_level_id', 'specialization_id', 'school_year'], 'uq_level_fees');
            });
        } catch (\Throwable $e) {
            // unique déjà présent: ignorer
        }

        try {
            Schema::table('level_fees', function (Blueprint $table) {
                $table->dropIndex('idx_level_fees_annexe_school_year');
            });
        } catch (\Throwable $e) {
            // index absent: ignorer
        }

        try {
            Schema::table('study_levels', function (Blueprint $table) {
                $table->dropIndex('idx_study_levels_annexe_code');
            });
        } catch (\Throwable $e) {
            // index absent: ignorer
        }

        try {
            Schema::table('specializations', function (Blueprint $table) {
                $table->dropIndex('idx_specializations_annexe_code');
            });
        } catch (\Throwable $e) {
            // index absent: ignorer
        }

        if (Schema::hasColumn('level_fees', 'annexe_id')) {
            Schema::table('level_fees', function (Blueprint $table) {
                $table->dropForeign(['annexe_id']);
                $table->dropColumn('annexe_id');
            });
        }

        if (Schema::hasColumn('study_levels', 'annexe_id')) {
            Schema::table('study_levels', function (Blueprint $table) {
                $table->dropForeign(['annexe_id']);
                $table->dropColumn('annexe_id');
            });
        }

        if (Schema::hasColumn('specializations', 'annexe_id')) {
            Schema::table('specializations', function (Blueprint $table) {
                $table->dropForeign(['annexe_id']);
                $table->dropColumn('annexe_id');
            });
        }
    }
};
