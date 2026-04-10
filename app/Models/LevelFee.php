<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LevelFee extends Model
{
    protected $fillable = [
        'annexe_id',
        'study_level_id',
        'specialization_id',
        'school_year',
        'tuition_amount',
        'notes',
    ];

    protected $casts = [
        'tuition_amount' => 'decimal:2',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function studyLevel(): BelongsTo
    {
        return $this->belongsTo(StudyLevel::class);
    }

    public function annexe(): BelongsTo
    {
        return $this->belongsTo(Annexe::class);
    }

    public function specialization(): BelongsTo
    {
        return $this->belongsTo(Specialization::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    // ── Méthode utilitaire ─────────────────────────────────────────────────────

    /**
     * Résout le tarif pour un niveau + filière + année donnés.
     * Fallback : tarif générique (specialization_id = null) si aucune ligne spécifique.
     */
    public static function resolve(int $studyLevelId, ?int $specializationId, string $schoolYear, ?string $annexeId = null): ?self
    {
        // 1. Tarif spécifique filière
        if ($specializationId) {
            $feeQuery = self::where('study_level_id', $studyLevelId)
                ->where('specialization_id', $specializationId)
                ->where('school_year', $schoolYear);

            if ($annexeId) {
                $feeQuery->where('annexe_id', $annexeId);
            }

            $fee = $feeQuery->first();

            if ($fee) return $fee;
        }

        // 2. Fallback: tarif générique du niveau (sans filière)
        $fallbackQuery = self::where('study_level_id', $studyLevelId)
            ->whereNull('specialization_id')
            ->where('school_year', $schoolYear);

        if ($annexeId) {
            $fallbackQuery->where('annexe_id', $annexeId);
        }

        return $fallbackQuery->first();
    }
}
