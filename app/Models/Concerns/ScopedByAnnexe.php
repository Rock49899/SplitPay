<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Isolation multi-tenant : restreint les requêtes aux annexes accessibles
 * par l'utilisateur authentifié.
 *
 * - Admin plateforme : aucune restriction.
 * - Super admin institution : annexes de SON institution uniquement.
 * - Autres utilisateurs : leurs annexes assignées.
 * - Pas d'utilisateur authentifié (webhook, espace étudiant, jobs) : aucune restriction,
 *   ces contextes filtrent eux-mêmes par étudiant / token.
 */
trait ScopedByAnnexe
{
    /**
     * IDs des annexes visibles, ou null si aucune restriction ne s'applique.
     */
    public static function tenantAnnexeIds(): ?array
    {
        $user = auth()->user();

        if (! $user instanceof User || $user->isPlatformAdmin()) {
            return null;
        }

        return $user->getAccessibleAnnexeIds();
    }

    /**
     * Filtre direct sur une colonne annexe_id de la table du modèle.
     */
    protected static function scopeToTenantAnnexes(Builder $query, string $column = 'annexe_id'): void
    {
        $annexeIds = static::tenantAnnexeIds();

        if ($annexeIds === null) {
            return;
        }

        if (empty($annexeIds)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereIn($query->qualifyColumn($column), $annexeIds);
    }

    /**
     * Filtre via la relation student (annexe de l'étudiant).
     */
    protected static function scopeToTenantAnnexesThroughStudent(Builder $query): void
    {
        $annexeIds = static::tenantAnnexeIds();

        if ($annexeIds === null) {
            return;
        }

        if (empty($annexeIds)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereHas('student', fn (Builder $q) => $q->whereIn('students.annexe_id', $annexeIds));
    }
}
