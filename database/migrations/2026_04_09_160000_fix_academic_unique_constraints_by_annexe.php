<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_levels', function (Blueprint $table) {
            try {
                $table->dropUnique(['code']);
            } catch (\Throwable $e) {
                try {
                    $table->dropUnique('study_levels_code_unique');
                } catch (\Throwable $e) {
                    // contrainte absente: ignorer
                }
            }

            try {
                $table->unique(['annexe_id', 'code'], 'uq_study_levels_annexe_code');
            } catch (\Throwable $e) {
                // contrainte déjà présente: ignorer
            }
        });

        Schema::table('specializations', function (Blueprint $table) {
            try {
                $table->dropUnique(['code']);
            } catch (\Throwable $e) {
                try {
                    $table->dropUnique('specializations_code_unique');
                } catch (\Throwable $e) {
                    // contrainte absente: ignorer
                }
            }

            try {
                $table->unique(['annexe_id', 'code'], 'uq_specializations_annexe_code');
            } catch (\Throwable $e) {
                // contrainte déjà présente: ignorer
            }
        });
    }

    public function down(): void
    {
        Schema::table('study_levels', function (Blueprint $table) {
            try {
                $table->dropUnique('uq_study_levels_annexe_code');
            } catch (\Throwable $e) {
                // contrainte absente: ignorer
            }

            try {
                $table->unique('code', 'study_levels_code_unique');
            } catch (\Throwable $e) {
                // contrainte déjà présente: ignorer
            }
        });

        Schema::table('specializations', function (Blueprint $table) {
            try {
                $table->dropUnique('uq_specializations_annexe_code');
            } catch (\Throwable $e) {
                // contrainte absente: ignorer
            }

            try {
                $table->unique('code', 'specializations_code_unique');
            } catch (\Throwable $e) {
                // contrainte déjà présente: ignorer
            }
        });
    }
};
