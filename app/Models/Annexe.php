<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Payment;

class Annexe extends Model
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
        'institution_id',
        'name',
        'address',
        'city',
        'annexe_details',
        'is_active',
    ];

    protected $appends = ['status'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'annexe_details' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Accesseur pour récupérer l'email depuis annexe_details
     */
    public function getEmailAttribute(): ?string
    {
        return $this->annexe_details['email'] ?? null;
    }

    /**
     * Accesseur pour récupérer le téléphone depuis annexe_details
     */
    public function getPhoneAttribute(): ?string
    {
        return $this->annexe_details['phone'] ?? null;
    }

    /**
     * Accesseur pour récupérer le fax depuis annexe_details
     */
    public function getFaxAttribute(): ?string
    {
        return $this->annexe_details['fax'] ?? null;
    }

    /**
     * Accesseur pour récupérer le site web depuis annexe_details
     */
    public function getWebsiteAttribute(): ?string
    {
        return $this->annexe_details['website'] ?? null;
    }

    /**
     * L'institution parente de cette annexe
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Tous les utilisateurs ayant accès à cette annexe (via user_annexes)
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_annexes')
            ->withPivot(['role_id', 'is_principal', 'assigned_by', 'assigned_at', 'end_at'])
            ->withTimestamps();
    }

    /**
     * Tous les étudiants de cette annexe
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Étudiants actifs uniquement
     */
    public function activeStudents(): HasMany
    {
        return $this->hasMany(Student::class)->where('status', 'active');
    }

    /**
     * Tous les rappels configurés pour cette annexe
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * Rappels actifs uniquement
     */
    public function activeReminders(): HasMany
    {
        return $this->hasMany(Reminder::class)->where('is_active', true);
    }

    /**
     * Toutes les notifications de cette annexe
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Notifications non lues uniquement
     */
    public function unreadNotifications(): HasMany
    {
        return $this->hasMany(Notification::class)->where('is_read', false);
    }

    /**
     * Vérifier si l'annexe est active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getStatusAttribute()
    {
    return $this->is_active ? 'active' : 'inactive';
    }

    /**
     * Désactiver l'annexe
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Activer l'annexe
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Calculer le montant total attendu (somme des scolarités)
     */
    public function totalExpectedAmount(): float
    {
        return $this->students()->sum('tuition_amount');
    }

    /**
     * Calculer le montant total collecté
     */
    public function totalCollectedAmount(): float
    {
        return $this->students()->sum('amount_paid');
    }

    /**
     * Calculer le taux de collecte en pourcentage
     */
    public function collectionRate(): float
    {
        $expected = $this->totalExpectedAmount();
        if ($expected == 0) {
            return 0;
        }
        
        return ($this->totalCollectedAmount() / $expected) * 100;
    }

  
    /**
     * Désactiver l'annexe et bloquer l'accès de ses utilisateurs (
     */
    public function deactivateWithUsers(): void
    {
        $this->update(['is_active' => false]);
        
    }

    /**
     * Statistiques complètes pour dashboard multi-annexes
     */
    public function getStatistics(): array
    {
        return [
            'annexe_id' => $this->id,
            'annexe_name' => $this->name,
            'total_students' => $this->students()->count(),
            'active_students' => $this->activeStudents()->count(),
            'expected_amount' => $this->totalExpectedAmount(),
            'collected_amount' => $this->totalCollectedAmount(),
            'collection_rate' => $this->collectionRate(),
            'total_payments' => Payment::whereHas('installment.paymentLink.student', function ($q) {
                $q->where('annexe_id', $this->id);
            })->where('status', 'success')->count(),
            'unread_notifications' => $this->unreadNotifications()->count(),
        ];
    }
}

