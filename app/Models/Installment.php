<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Installment extends Model
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
        'payment_link_id',
        'tranche_number',
        'description',
        'amount',
        'due_date',
        'amount_paid',
        'status',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    /**
     * Le lien de paiement associé
     */
    public function paymentLink(): BelongsTo
    {
        return $this->belongsTo(PaymentLink::class);
    }

    /**
     * L'utilisateur qui a créé cette échéance
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Tous les paiements pour cette échéance
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Paiements réussis uniquement
     */
    public function successfulPayments(): HasMany
    {
        return $this->hasMany(Payment::class)->where('status', 'success');
    }

    /**
     * Montant restant à payer pour cette échéance
     */
    public function getRemainingAmountAttribute(): float
    {
        return $this->amount - $this->amount_paid;
    }

    /**
     * Vérifier si l'échéance est entièrement payée
     */
    public function isFullyPaid(): bool
    {
        return $this->amount_paid >= $this->amount;
    }

    /**
     * Vérifier si l'échéance est active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Vérifier si l'échéance est expirée
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired' || 
               ($this->due_date && $this->due_date->isPast());
    }

    /**
     * Vérifier si l'échéance est en retard
     */
    public function isOverdue(): bool
    {
        return $this->isActive() && 
               !$this->isFullyPaid() && 
               $this->due_date && 
               $this->due_date->isPast();
    }

    /**
     * Marquer l'échéance comme utilisée
     */
    public function markAsUsed(): void
    {
        $this->update(['status' => 'used']);
    }

    /**
     * Marquer l'échéance comme expirée
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    /**
     * Enregistrer un paiement pour cette échéance
     */
    public function recordPayment(float $amount): void
   {
    $this->increment('amount_paid', $amount);

    if ($this->fresh()->isFullyPaid()) {
        $this->markAsUsed();
    }

    // vérifier si le lien complet est soldé
    $this->paymentLink->refreshStatus();
    }


    /**
     * Scope pour échéances actives
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope pour échéances expirées
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * Scope pour échéances en retard
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'active')
                     ->where('due_date', '<', now())
                     ->whereRaw('amount_paid < amount');
    }

    /**
     * Scope pour échéances à venir dans X jours
     */
    public function scopeDueInDays($query, int $days)
    {
        return $query->where('status', 'active')
                     ->whereBetween('due_date', [now(), now()->addDays($days)])
                     ->whereRaw('amount_paid < amount');
    }
}
