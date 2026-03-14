<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

class PaymentLink extends Model
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
     * Disable updated_at timestamp
     *
     * @var bool
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'student_id',
        'school_year',
        'type',
        'token',
        'amount',
        'currency',
        'description',
        'due_date',
        'status',
        'sent_at',
        'expire_at',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'sent_at' => 'datetime',
        'expire_at' => 'datetime',
    ];
    /**
     * Relation paiement(s) directs pour ce lien
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'payment_link_id');
    }

    /**
     * L'étudiant associé (ancien schéma direct)
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Student::class);
    }

    //un lien pour plusieurs paiements
//     public function payments(): HasMany
//    {
//     return $this->hasMany(Payment::class, 'payment_link_id');
//    }

   // si les paiements liés au lien sont totalement payés
   public function isFullyPaid(): bool
   {
    return $this->installments()
        ->whereRaw('amount_paid < amount')
        ->count() === 0;
   }

   //recalculer automatiquement le statut du lien selon les paiements associés
   public function refreshStatus(): void
   {
    if ($this->isFullyPaid()) {
        $this->update(['status' => 'used']);
    }
   }

    /**
     * Toutes les échéances de ce lien
     */
    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }

    /**
     * Échéances actives uniquement
     */
    public function activeInstallments(): HasMany
    {
        return $this->hasMany(Installment::class)->where('status', 'active');
    }

    /**
     * Générer un token unique sécurisé
     */
    public static function generateUniqueToken(): string
    {
        do {
            $token = Str::random(64);
        } while (self::where('token', $token)->exists());

        return $token;
    }

    /**
     * Générer l'URL complète du lien de paiement
     */
    public function getUrlAttribute(): string
    {
        return url("/payment/{$this->token}");
    }

    /**
     * Vérifier si le lien est actif
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Vérifier si le lien est expiré
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired' || 
               ($this->expire_at && $this->expire_at->isPast());
    }

    /**
     * Vérifier si le lien est utilisé
     */
    public function isUsed(): bool
    {
        return $this->status === 'used';
    }

    /**
     * Vérifier si le lien est valide (actif et non expiré)
     */
    public function isValid(): bool
    {
        return $this->isActive() && !$this->isExpired();
    }

    /**
     * Marquer le lien comme envoyé
     */
    public function markAsSent(): void
    {
        $this->update(['sent_at' => now()]);
    }

    /**
     * Marquer le lien comme utilisé
     */
    public function markAsUsed(): void
    {
        $this->update(['status' => 'used']);
    }

    /**
     * Marquer le lien comme expiré
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    /**
     * Calculer le montant total payé via ce lien
     */
   public function totalPaid(): float
   { 
    return $this->installments()
        ->with('payments')
        ->get()
        ->flatMap->payments
        ->where('status', 'success')
        ->sum('amount');
   }


    /**
     * Calculer le montant restant à payer
     */
    public function remainingAmount(): float
    {
        return $this->amount - $this->totalPaid();
    }

    /**
     * Scope pour liens actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope pour liens expirés
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
                     ->orWhere('expire_at', '<', now());
    }

    /**
     * Scope pour liens envoyés
     */
    public function scopeSent($query)
    {
        return $query->whereNotNull('sent_at');
    }

    /**
     * Scope pour liens non envoyés
     */
    public function scopeNotSent($query)
    {
        return $query->whereNull('sent_at');
    }

    /**
     * liens de paiement filtrés selon l'annexe de l'étudiant associé
     */
    protected static function booted()
    {
        static::addGlobalScope('annexe', function (Builder $query) {

            $user = auth()->user();

            if (!$user) {
                return;
            }

            if (method_exists($user, 'isSuperAdminInstitution') 
                && $user->isSuperAdminInstitution()) {
                return;
            }

            if (!method_exists($user, 'getAccessibleAnnexeIds')) {
                return;
            }

            $annexeIds = $user->getAccessibleAnnexeIds();

            if (!empty($annexeIds)) {
                $query->whereHas('student', function ($q) use ($annexeIds) {
                    $q->whereIn('annexe_id', $annexeIds);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        });
    }

}

