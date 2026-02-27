<?php

namespace App\Http\Controllers\Traits;

use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;

trait FiltersByAnnexe
{
    /**
     * Récupère les IDs des annexes de l'utilisateur connecté
     */
    protected function getUserAnnexeIds(): array
    {
        $user = auth()->user();
        return $user->annexes->pluck('id')->toArray();
    }

    /**
     * Vérifie si l'utilisateur est super admin institution
     */
    protected function isSuperAdminInstitution(): bool
    {
        $user = auth()->user();
        
        // Vérifier d'abord par la colonne scope (plus simple et direct)
        if (isset($user->scope) && $user->scope === 'institution') {
            return true;
        }
        
        // Fallback: vérifier par rôle (pour compatibilité)
        foreach ($user->annexes as $annexe) {
            $role = Role::find($annexe->pivot->role_id);
            if ($role && $role->code === 'super_admin_institution') {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Applique un filtre par annexe sur une query si l'utilisateur n'est pas super admin institution
     * 
     * @param Builder $query
     * @param string $column Nom de la colonne annexe_id (par défaut 'annexe_id')
     * @return Builder
     */
    protected function scopeByUserAnnexes(Builder $query, string $column = 'annexe_id'): Builder
    {
        // Si super admin institution, voir toutes les données
        if ($this->isSuperAdminInstitution()) {
            return $query;
        }

        // Sinon, filtrer par les annexes de l'utilisateur
        $annexeIds = $this->getUserAnnexeIds();
        
        if (empty($annexeIds)) {
            // Si l'utilisateur n'a aucune annexe, retourner une query vide
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($column, $annexeIds);
    }

    /**
     * Récupère l'ID de l'annexe principale de l'utilisateur
     */
    protected function getUserPrincipalAnnexeId(): ?string
    {
        $user = auth()->user();
        $principalAnnexe = $user->annexes()->wherePivot('is_principal', true)->first();
        
        return $principalAnnexe?->id;
    }

    /**
     * Vérifie si l'utilisateur a accès à une annexe spécifique
     */
    protected function userHasAccessToAnnexe(string $annexeId): bool
    {
        if ($this->isSuperAdminInstitution()) {
            return true;
        }

        return in_array($annexeId, $this->getUserAnnexeIds());
    }
}
