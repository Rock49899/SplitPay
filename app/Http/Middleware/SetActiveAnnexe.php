<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SetActiveAnnexe
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Récupérer l'ID de l'annexe active depuis le header
        $activeAnnexeId = $request->header('X-Active-Annexe-Id');
        
        if ($activeAnnexeId) {
            $user = $request->user();
            
            // Vérifier que l'utilisateur a bien accès à cette annexe
            if ($user) {
                try {
                    // Super admin institution a accès à toutes les annexes
                    if ($user->scope === 'institution') {
                        $request->attributes->set('active_annexe_id', $activeAnnexeId);
                    } else {
                        // Vérifier que l'utilisateur est assigné à cette annexe
                        $hasAccess = $user->annexes()->where('annexes.id', $activeAnnexeId)->exists();
                        
                        if ($hasAccess) {
                            $request->attributes->set('active_annexe_id', $activeAnnexeId);
                        } else {
                            // L'utilisateur n'a pas accès à cette annexe
                            return response()->json([
                                'message' => 'Accès non autorisé à cette annexe',
                                'error' => 'unauthorized_annexe_access'
                            ], 403);
                        }
                    }
                } catch (\Exception $e) {
                    // Log l'erreur mais ne pas bloquer la requête
                    Log::error('SetActiveAnnexe middleware error: ' . $e->getMessage());
                    // Continuer sans définir l'annexe active
                }
            }
        }
        
        return $next($request);
    }
}
