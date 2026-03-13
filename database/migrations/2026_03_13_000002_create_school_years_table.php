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
        Schema::create('school_years', function (Blueprint $table) {
            $table->id();
            $table->string('year', 20)->unique(); // ex: 2025-2026
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('promoted_to_year', 20)->nullable();
            $table->timestamps();

            $table->index('status');
        });

        // Backfill depuis les enrollments existants
        $years = DB::table('enrollments')
            ->select('school_year')
            ->whereNotNull('school_year')
            ->distinct()
            ->pluck('school_year')
            ->filter()
            ->values()
            ->all();

        // Ajouter l'année académique courante au cas où la DB est vide
        $now = now();
        $base = $now->month >= 9 ? $now->year : $now->year - 1;
        $currentYear = $base . '-' . ($base + 1);
        if (!in_array($currentYear, $years, true)) {
            $years[] = $currentYear;
        }

        // Trier décroissant pour définir la plus récente comme active
        usort($years, fn ($a, $b) => strcmp($b, $a));

        foreach ($years as $index => $year) {
            DB::table('school_years')->insert([
                'year' => $year,
                'status' => $index === 0 ? 'active' : 'closed',
                'opened_at' => $index === 0 ? now() : null,
                'closed_at' => $index === 0 ? null : now(),
                'promoted_to_year' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_years');
    }
};
