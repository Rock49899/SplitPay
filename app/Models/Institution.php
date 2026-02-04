<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
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
        'name',
        'email',
        'phone',
        'address',
        'city',
        'logo',
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
     * Toutes les annexes de cette institution
     */
    public function annexes(): HasMany
    {
        return $this->hasMany(Annexe::class);
    }

    /**
     * Annexes actives uniquement
     */
    public function activeAnnexes(): HasMany
    {
        return $this->hasMany(Annexe::class)->where('is_active', true);
    }

    /**
     * Vérifier si l'institution est active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Désactiver l'institution et toutes ses annexes
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
        $this->annexes()->update(['is_active' => false]);
    }

    /**
     * Activer l'institution
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Utilisé pour le dashboard du Super Admin Institution
     */
    public function getConsolidatedStatistics(): array
    {
        $stats = [
            'institution_name' => $this->name,
            'total_annexes' => $this->annexes()->count(),
            'active_annexes' => $this->activeAnnexes()->count(),
            'total_students' => 0,
            'total_expected' => 0,
            'total_collected' => 0,
            'annexes_details' => [],
        ];

        foreach ($this->annexes as $annexe) {
            $annexeStats = $annexe->getStatistics();
            $stats['annexes_details'][] = $annexeStats;
            $stats['total_students'] += $annexeStats['total_students'];
            $stats['total_expected'] += $annexeStats['expected_amount'];
            $stats['total_collected'] += $annexeStats['collected_amount'];
        }

        $stats['global_collection_rate'] = $stats['total_expected'] > 0 
            ? ($stats['total_collected'] / $stats['total_expected']) * 100 
            : 0;

        return $stats;
    }

    /**
     * Comparaison des performances entre annexes
     */
    public function compareAnnexesPerformance(): array
    {
        $comparison = [];

        foreach ($this->annexes as $annexe) {
            $comparison[] = [
                'annexe_id' => $annexe->id,
                'annexe_name' => $annexe->name,
                'collection_rate' => $annexe->collectionRate(),
                'total_payments' => Payment::whereHas('installment.paymentLink.student', function ($q) use ($annexe) {
                    $q->where('annexe_id', $annexe->id);
                })->where('status', 'success')->count(),
                'expected_amount' => $annexe->totalExpectedAmount(),
                'collected_amount' => $annexe->totalCollectedAmount(),
            ];
        }

        // Trier par taux de collecte décroissant
        usort($comparison, function ($a, $b) {
            return $b['collection_rate'] <=> $a['collection_rate'];
        });

        return $comparison;
    }
}

