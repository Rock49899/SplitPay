<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait FiltersByAnnexe
{
    /**
     * Récupère les annexes accessibles pour l'utilisateur courant.
     *
     * - Super admin institution : toutes les annexes de son institution.
     * - Autres utilisateurs : uniquement les annexes qui leur sont assignées.
     */
    protected function getAccessibleAnnexeIds(): array
    {
        $user = auth()->user();

        if (!$user) {
            return [];
        }

        return array_values(array_filter($user->getAccessibleAnnexeIds()));
    }

    /**
     * Récupère l'ID de l'annexe active depuis le contexte de la requête
     */
    protected function getActiveAnnexeId(): ?string
    {
        $request = request();
        
        // 1. Priorité au header X-Active-Annexe-Id (défini par le middleware)
        $activeAnnexeId = $request->attributes->get('active_annexe_id');
        
        if ($activeAnnexeId) {
            return $activeAnnexeId;
        }
        
        // 2. Fallback: annexe principale de l'utilisateur
        $user = auth()->user();
        if ($user) {
            try {
                $principalAnnexe = $user->annexes()->wherePivot('is_principal', true)->first();
                if ($principalAnnexe) {
                    return $principalAnnexe->id;
                }
                
                // 3. Si pas d'annexe principale, prendre la première
                $firstAnnexe = $user->annexes()->first();
                if ($firstAnnexe) {
                    return $firstAnnexe->id;
                }
            } catch (\Exception $e) {
                Log::error('Error fetching active annexe: ' . $e->getMessage());
            }
        }
        
        return null;
    }
    
    /**
     * Récupère les IDs des annexes de l'utilisateur connecté
     */
    protected function getUserAnnexeIds(): array
    {
        $user = auth()->user();
        
        if (!$user) {
            return [];
        }
        
        try {
            return $user->annexes()->pluck('annexes.id')->toArray();
        } catch (\Exception $e) {
            Log::error('Error fetching user annexes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère l'ID de l'institution de l'utilisateur connecté
     */
    protected function getCurrentInstitutionId(): ?string
    {
        return auth()->user()?->institutionId();
    }

    /**
     * Vérifie si l'utilisateur est super admin institution (ou admin plateforme)
     */
    protected function isSuperAdminInstitution(): bool
    {
        return (bool) auth()->user()?->isSuperAdminInstitution();
    }

    /**
     * Applique un filtre par annexe active sur une query
     *
     * IMPORTANT:
     * - Super admin institution → toutes les annexes de SON institution
     * - Users multi-annexe → Filtre uniquement sur l'annexe ACTIVE
     * 
     * @param Builder $query
     * @param string $column Nom de la colonne annexe_id (par défaut 'annexe_id')
     * @return Builder
     */
    protected function scopeByUserAnnexes(Builder $query, string $column = 'annexe_id'): Builder
    {
        $allowedAnnexeIds = $this->isSuperAdminInstitution()
            ? $this->getAccessibleAnnexeIds()
            : [$this->getActiveAnnexeId()];

        $allowedAnnexeIds = array_values(array_filter($allowedAnnexeIds));

        if (empty($allowedAnnexeIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($column, $allowedAnnexeIds);
    }

    /**
     * Récupère l'ID de l'annexe principale de l'utilisateur
     */
    protected function getUserPrincipalAnnexeId(): ?string
    {
        $user = auth()->user();
        
        if (!$user) {
            return null;
        }
        
        try {
            $principalAnnexe = $user->annexes()->wherePivot('is_principal', true)->first();
            return $principalAnnexe?->id;
        } catch (\Exception $e) {
            Log::error('Error fetching principal annexe: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Vérifie si l'utilisateur a accès à une annexe spécifique
     */
    protected function userHasAccessToAnnexe(string $annexeId): bool
    {
        return in_array($annexeId, $this->getAccessibleAnnexeIds());
    }
}

