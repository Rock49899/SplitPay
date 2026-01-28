<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reminder extends Model
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
        'annexe_id',
        'days_before',
        'message',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * L'annexe à laquelle appartient ce rappel
     */
    public function annexe(): BelongsTo
    {
        return $this->belongsTo(Annexe::class);
    }

    /**
     * Vérifier si le rappel est actif
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Activer le rappel
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Désactiver le rappel
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Remplacer les variables dans le message
     */
    public function parseMessage(array $data): string
    {
        $message = $this->message;

        foreach ($data as $key => $value) {
            $message = str_replace("{{$key}}", $value, $message);
        }

        return $message;
    }

    /**
     * Récupérer les échéances concernées par ce rappel
     */
    public function getTargetInstallments()
    {
        $targetDate = now()->addDays($this->days_before);

        return Installment::whereHas('paymentLink.student', function ($query) {
            $query->where('annexe_id', $this->annexe_id);
        })
        ->where('status', 'active')
        ->whereDate('due_date', $targetDate->toDateString())
        ->whereRaw('amount_paid < amount')
        ->get();
    }

    /**
     * Scope pour rappels actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour rappels d'une annexe
     */
    public function scopeForAnnexe($query, $annexeId)
    {
        return $query->where('annexe_id', $annexeId);
    }

    /**
     * Scope pour rappels triés par nombre de jours
     */
    public function scopeOrderedByDays($query)
    {
        return $query->orderBy('days_before', 'desc');
    }
}
