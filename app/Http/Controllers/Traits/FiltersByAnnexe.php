<?php

namespace App\Http\Controllers\Traits;

use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait FiltersByAnnexe
{
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
     * Vérifie si l'utilisateur est super admin institution
     */
    protected function isSuperAdminInstitution(): bool
    {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }
        
         // Vérifier d'abord par la colonne scope (plus simple et direct)
        if (isset($user->scope) && $user->scope === 'institution') {
            return true;
        }
        
        // Fallback: vérifier par rôle (pour compatibilité)
        try {
            foreach ($user->annexes()->get() as $annexe) {
                $role = Role::find($annexe->pivot->role_id);
                if ($role && $role->code === 'super_admin_institution') {
                    return true;
                }
            }
        } catch (\Exception $e) {
            Log::error('Error checking super admin status: ' . $e->getMessage());
        }
        
        return false;
    }

    /**
     * Applique un filtre par annexe active sur une query
     * 
     * IMPORTANT: 
     * - Super admin institution → AUCUN filtre (voit toutes les annexes)
     * - Users multi-annexe → Filtre uniquement sur l'annexe ACTIVE
     * 
     * @param Builder $query
     * @param string $column Nom de la colonne annexe_id (par défaut 'annexe_id')
     * @return Builder
     */
    protected function scopeByUserAnnexes(Builder $query, string $column = 'annexe_id'): Builder
    {
        // Si super admin institution → AUCUN filtre, voit toutes les annexes
        if ($this->isSuperAdminInstitution()) {
            return $query;
        }
        
        // Pour les users multi-annexe → Récupérer l'annexe active
        $activeAnnexeId = $this->getActiveAnnexeId();
        
        if (!$activeAnnexeId) {
            // Si aucune annexe active, retourner une query vide
            return $query->whereRaw('1 = 0');
        }
        
        // Filtrer UNIQUEMENT sur l'annexe active
        return $query->where($column, $activeAnnexeId);
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
        if ($this->isSuperAdminInstitution()) {
            return true;
        }

        return in_array($annexeId, $this->getUserAnnexeIds());
    }
}

