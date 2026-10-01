<?php

namespace App\Models;

use App\Models\Concerns\ScopedByAnnexe;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Notification extends Model
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
        'annexe_id',
        'title',
        'message',
        'type',
        'is_read',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    /**
     * L'annexe à laquelle appartient cette notification
     */
    public function annexe(): BelongsTo
    {
        return $this->belongsTo(Annexe::class);
    }

    /**
     * Vérifier si la notification est lue
     */
    public function isRead(): bool
    {
        return $this->is_read;
    }

    /**
     * Marquer comme lue
     */
    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }

    /**
     * Marquer comme non lue
     */
    public function markAsUnread(): void
    {
        $this->update(['is_read' => false]);
    }

    /**
     * Créer une notification de paiement réussi
     */
    public static function paymentReceived(Payment $payment): self
    {
        $student = $payment->student;
        $annexe = $student->annexe;

        return self::create([
            'annexe_id' => $annexe->id,
            'title' => 'Paiement reçu',
            'message' => "Paiement de {$payment->amount} FCFA reçu de {$student->full_name} ({$student->matricule})",
            'type' => 'payment_received',
        ]);
    }

    /**
     * Créer une notification de paiement échoué
     */
    public static function paymentFailed(Payment $payment): self
    {
        $student = $payment->student;
        $annexe = $student->annexe;

        return self::create([
            'annexe_id' => $annexe->id,
            'title' => 'Paiement échoué',
            'message' => "Échec du paiement de {$payment->amount} FCFA pour {$student->full_name} ({$student->matricule})",
            'type' => 'payment_failed',
        ]);
    }

    /**
     * Créer une notification d'échéance proche
     */
    public static function dueDateApproaching(Installment $installment, int $daysLeft): self
    {
        $student = $installment->paymentLink->student;
        $annexe = $student->annexe;

        return self::create([
            'annexe_id' => $annexe->id,
            'title' => 'Échéance proche',
            'message' => "Échéance de {$installment->amount} FCFA pour {$student->full_name} dans {$daysLeft} jour(s)",
            'type' => 'due_date_approaching',
        ]);
    }

    /**
     * Créer une notification d'échéance dépassée
     */
    public static function overduePayment(Installment $installment): self
    {
        $student = $installment->paymentLink->student;
        $annexe = $student->annexe;

        return self::create([
            'annexe_id' => $annexe->id,
            'title' => 'Échéance dépassée',
            'message' => "Échéance de {$installment->amount} FCFA dépassée pour {$student->full_name} ({$student->matricule})",
            'type' => 'payment_overdue',
        ]);
    }

    /**
     * Scope pour notifications non lues
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope pour notifications lues
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope pour notifications par type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope pour notifications d'une annexe
     */
    public function scopeForAnnexe($query, $annexeId)
    {
        return $query->where('annexe_id', $annexeId);
    }

    /**
     * Scope pour notifications récentes
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope pour notifications triées par date (plus récentes en premier)
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }


    /**
     * haque utilisateur ne voit que les notifications de ses annexes
     */
    protected static function booted()
    {
        static::addGlobalScope('annexe', function (Builder $query) {
            static::scopeToTenantAnnexes($query);
        });
    }
}

