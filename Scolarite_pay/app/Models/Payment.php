<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
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
        'installment_id',
        'reference',
        'amount',
        'method',
        'status',
        'payplus_transaction_id',
        'payer_name',
        'payer_email',
        'payer_phone',
        'payment_date',
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
            'payment_date' => 'datetime',
        ];
    }

    /**
     * L'échéance associée à ce paiement
     */
    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class);
    }

    /**
     * Générer une référence unique
     */
    public static function generateReference(): string
    {
        do {
            $reference = 'PAY-' . strtoupper(uniqid());
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Vérifier si le paiement est en attente
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Vérifier si le paiement est réussi
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Vérifier si le paiement a échoué
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Marquer le paiement comme réussi
     */
    public function markAsSuccess(string $transactionId = null): void
    {
        $data = [
            'status' => 'success',
            'payment_date' => now(),
        ];

        if ($transactionId) {
            $data['payplus_transaction_id'] = $transactionId;
        }

        $this->update($data);

        // Mettre à jour l'échéance
        $this->installment->recordPayment($this->amount);

        // Mettre à jour le montant payé de l'étudiant
        $student = $this->installment->paymentLink->student;
        $student->recordPayment($this->amount);
    }

    /**
     * Marquer le paiement comme échoué
     */
    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);
    }

    /**
     * Récupérer l'étudiant via l'échéance et le lien
     */
    public function getStudentAttribute()
    {
        return $this->installment->paymentLink->student;
    }

    /**
     * Scope pour paiements réussis
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope pour paiements en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope pour paiements échoués
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope pour paiements d'une période
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    /**
     * Scope pour paiements du mois en cours
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('payment_date', now()->month)
                     ->whereYear('payment_date', now()->year);
    }

    /**
     * Scope pour paiements par méthode
     */
    public function scopeByMethod($query, string $method)
    {
        return $query->where('method', $method);
    }
}
