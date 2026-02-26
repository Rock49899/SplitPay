<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Annexe;
use App\Models\PaymentLink;
use App\Models\Payment;

class Student extends Model
{
    use HasFactory, HasUuids;

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
        'class',
        'school_year',
        'tuition_amount',
        'amount_paid',
        'status',
        'study_level_id',
        'specialization_id',
        'class_id',
    ];

    /**
     * Casts pour les attributs numériques
     */
    protected $casts = [
        'tuition_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

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
        return Storage::disk('public')->url($path);
    }

    /**
     * L'annexe à laquelle appartient l'étudiant
     */
    public function annexe(): BelongsTo
    {
        return $this->belongsTo(Annexe::class);
    }

    /**
     * Le niveau d'études de l'étudiant
     */
    public function studyLevel(): BelongsTo
    {
        return $this->belongsTo(StudyLevel::class, 'study_level_id');
    }

    /**
     * La spécialisation/filière de l'étudiant
     */
    public function specialization(): BelongsTo
    {
        return $this->belongsTo(Specialization::class, 'specialization_id');
    }

    /**
     * La classe de l'étudiant
     */
    public function studentClass(): BelongsTo
    {
        return $this->belongsTo(StudentClass::class, 'class_id');
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
     * Montant restant à payer
     */
    public function getRemainingAmountAttribute(): float
    {
        return $this->tuition_amount - $this->amount_paid;
    }

    /**
     * Taux de paiement en pourcentage
     */
    public function getPaymentRateAttribute(): float
    {
        if ($this->tuition_amount == 0) {
            return 0;
        }
        
        return ($this->amount_paid / $this->tuition_amount) * 100;
    }

    /**
     * Vérifier si l'étudiant est actif
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Vérifier si l'étudiant a terminé de payer
     */
    public function hasFullyPaid(): bool
    {
        return $this->amount_paid >= $this->tuition_amount;
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
     * Enregistrer un paiement
     */
    public function recordPayment(float $amount): void
    {
        $this->increment('amount_paid', $amount);
    }

    /**
     * Scope pour filtrer par année scolaire
     */
    public function scopeForSchoolYear($query, string $schoolYear)
    {
        return $query->where('school_year', $schoolYear);
    }

    /**
     * Scope pour filtrer par classe
     */
    public function scopeInClass($query, string $class)
    {
        return $query->where('class', $class);
    }

    /**
     * Scope pour étudiants actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope pour étudiants avec paiement incomplet
     */
    public function scopeWithOutstandingBalance($query)
    {
        return $query->whereRaw('amount_paid < tuition_amount');
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
     * chaque utilisateur ne voit que les étudiants de ses annexes
     * Sauf Super Admin Institution qui voit tout
     */
    protected static function booted()
    {
        static::addGlobalScope('annexe', function (Builder $query) {
            if (auth()->check() && !auth()->user()->isSuperAdminInstitution()) {
                $annexeIds = auth()->user()->getAccessibleAnnexeIds();
                if (!empty($annexeIds)) {
                    $query->whereIn('annexe_id', $annexeIds);
                } else {
                    // Si l'utilisateur n'a accès à aucune annexe, ne rien retourner
                    $query->whereRaw('1 = 0');
                }
            }
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
