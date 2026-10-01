<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Année scolaire d'une institution (chaque institution a son propre calendrier).
 */
class SchoolYear extends Model
{
    protected $fillable = [
        'institution_id',
        'year',
        'status',
        'opened_at',
        'closed_at',
        'promoted_to_year',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    /**
     * Restreint à une institution. Sans institution (admin plateforme hors contexte),
     * aucune restriction n'est appliquée.
     */
    public function scopeForInstitution($query, ?string $institutionId)
    {
        return $institutionId ? $query->where('institution_id', $institutionId) : $query;
    }

    /**
     * Année académique courante (septembre → août), ex: "2025-2026".
     */
    public static function currentYearLabel(): string
    {
        $now = now();
        $base = $now->month >= 9 ? $now->year : $now->year - 1;

        return $base . '-' . ($base + 1);
    }

    /**
     * Garantit qu'une institution possède au moins une année active
     * (nouvelle institution, ou institution créée avant le découpage par institution).
     */
    public static function ensureForInstitution(string $institutionId): void
    {
        if (self::where('institution_id', $institutionId)->exists()) {
            return;
        }

        self::firstOrCreate(
            ['institution_id' => $institutionId, 'year' => self::currentYearLabel()],
            ['status' => 'active', 'opened_at' => now()]
        );
    }
}
