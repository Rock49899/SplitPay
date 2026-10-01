<?php

namespace App\Models;

use App\Models\Concerns\ScopedByAnnexe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    use ScopedByAnnexe;

    /**
     * Chaque utilisateur ne voit que les inscriptions des étudiants de ses annexes
     */
    protected static function booted()
    {
        static::addGlobalScope('annexe', function (Builder $query) {
            static::scopeToTenantAnnexesThroughStudent($query);
        });
    }

    protected $fillable = [
        'student_id',
        'level_fee_id',
        'tuition_amount',
        'amount_paid',
        'school_year',
        'status',
        'promoted_at',
        'notes',
    ];

    protected $casts = [
        'tuition_amount' => 'decimal:2',
        'amount_paid'    => 'decimal:2',
        'promoted_at'    => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function levelFee(): BelongsTo
    {
        return $this->belongsTo(LevelFee::class);
    }

    // ── Accessors pratiques ───────────────────────────────────────────────────

    /**
     * Montant restant à payer sur cet enrollment.
     */
    public function getRemainingAttribute(): float
    {
        return max(0, (float) $this->tuition_amount - (float) $this->amount_paid);
    }

    /**
     * Taux de recouvrement (0–100).
     */
    public function getRecoveryRateAttribute(): float
    {
        if ((float) $this->tuition_amount === 0.0) return 100.0;
        return round((float) $this->amount_paid / (float) $this->tuition_amount * 100, 1);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForYear($query, string $year)
    {
        return $query->where('school_year', $year);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
