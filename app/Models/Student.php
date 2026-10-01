<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Concerns\ScopedByAnnexe;
use App\Models\Enrollment;

class Student extends Model
{
    use HasFactory, HasUuids, ScopedByAnnexe;

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'annexe_id',
        'matricule',
        'first_name',
        'last_name',
        'email',
        'phone',
        'avatar',
        'specialization_id', // filière semi-permanente (ne change pas d'année en année)
        'status',
    ];

    protected $casts = [];

    /**
     * Attributs ajoutés au JSON retourné (compatibilité frontend)
     */
    protected $appends = ['is_active', 'avatar_url'];

    /**
     * Accessor : is_active (true si status === 'active')
     */
    public function getIsActiveAttribute(): bool
    {
        return ($this->attributes['status'] ?? null) === 'active';
    }

    /**
     * Accessor : avatar_url — URL publique de l'avatar ou null
     */
    public function getAvatarUrlAttribute(): ?string
    {
        $path = $this->attributes['avatar'] ?? null;
        if (!$path) return null;
        
        // Retourner une URL relative au lieu d'absolue pour éviter les problèmes de domaine
        return '/storage/' . $path;
    }

    /**
     * L'annexe à laquelle appartient l'étudiant
     */
    public function annexe(): BelongsTo
    {
        return $this->belongsTo(Annexe::class);
    }

    /**
     * La filière de l'étudiant (semi-permanent, stocké sur students pour filtrage rapide).
     * Le niveau d'études courant est accessible via currentEnrollment → levelFee → studyLevel.
     */
    public function specialization(): BelongsTo
    {
        return $this->belongsTo(Specialization::class, 'specialization_id');
    }

    /**
     * Tous les enrollments (historique annuel) de l'étudiant, du plus récent au plus ancien.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class)->orderBy('school_year', 'desc');
    }

    /**
     * Enrollment actif le plus récent (année courante).
     * Utilisé comme source de vérité pour tuition_amount / amount_paid.
     */
    public function currentEnrollment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Enrollment::class)
            ->where('status', 'active')
            ->ofMany('school_year', 'max');
    }

    /**
     * Tous les liens de paiement de cet étudiant
     */
    public function paymentLinks(): HasMany
    {
        return $this->hasMany(PaymentLink::class);
    }

    /**
     * Liens de paiement actifs uniquement
     */
    public function activePaymentLinks(): HasMany
    {
        return $this->hasMany(PaymentLink::class)->where('status', 'active');
    }

    /**
     * Tous les paiements de cet étudiant (via installments)
     */
    public function paymentsQuery()
    {
        return Payment::whereHas('installment.paymentLink', function ($query) {
            $query->where('student_id', $this->id);
        });
    }

    /**
     * Nom complet de l'étudiant
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Montant restant à payer (délégué à currentEnrollment).
     */
    public function getRemainingAmountAttribute(): float
    {
        return $this->currentEnrollment?->remaining ?? 0.0;
    }

    /**
     * Taux de paiement en pourcentage (délégué à currentEnrollment).
     */
    public function getPaymentRateAttribute(): float
    {
        return $this->currentEnrollment?->recovery_rate ?? 0.0;
    }

    /**
     * Vérifier si l'étudiant est actif
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Vérifier si l'étudiant a terminé de payer (délégué à currentEnrollment).
     */
    public function hasFullyPaid(): bool
    {
        $enrollment = $this->currentEnrollment;
        return $enrollment ? (float) $enrollment->amount_paid >= (float) $enrollment->tuition_amount : false;
    }

    /**
     * Suspendre l'étudiant
     */
    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    /**
     * Activer l'étudiant
     */
    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    /**
     * Marquer comme diplômé
     */
    public function graduate(): void
    {
        $this->update(['status' => 'graduated']);
    }

    /**
     * Enregistrer un paiement sur l'enrollment actif.
     */
    public function recordPayment(float $amount): void
    {
        $enrollment = $this->currentEnrollment;
        if ($enrollment) {
            $enrollment->increment('amount_paid', $amount);
            if ((float) $enrollment->fresh()->amount_paid >= (float) $enrollment->tuition_amount) {
                $enrollment->update(['status' => 'completed']);
            }
        }
    }

    /**
     * Scope : étudiants qui ont un enrollment pour l'année donnée.
     */
    public function scopeForSchoolYear($query, string $schoolYear)
    {
        return $query->whereHas('enrollments', fn($q) => $q->where('school_year', $schoolYear));
    }

    /**
     * Scope pour étudiants actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope : étudiants avec solde impayé sur leur enrollment actif.
     */
    public function scopeWithOutstandingBalance($query)
    {
        return $query->whereHas('currentEnrollment',
            fn($q) => $q->whereRaw('amount_paid < tuition_amount')
        );
    }

    /**
     * Scope pour recherche
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('matricule', 'like', "%{$term}%")
              ->orWhere('first_name', 'like', "%{$term}%")
              ->orWhere('last_name', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%");
        });
    }


    /**
     * Chaque utilisateur ne voit que les étudiants de ses annexes
     * (super admin institution : toutes les annexes de son institution).
     */
    protected static function booted()
    {
        static::addGlobalScope('annexe', function (Builder $query) {
            static::scopeToTenantAnnexes($query);
        });
    }

    /**
     * Transférer l'étudiant vers une autre annexe (Super Admin Institution)
     */
    public function transferToAnnexe(string $newAnnexeId): void
    {
        $this->update(['annexe_id' => $newAnnexeId]);
        // L'historique des paiements reste lié à l'étudiant
    }
}
