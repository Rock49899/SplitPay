<?php

namespace App\Http\Middleware;

use App\Models\Annexe;
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
        $activeAnnexeId = $request->header('X-Active-Annexe-Id');

        if ($activeAnnexeId) {
            $user = $request->user();

            if ($user) {
                try {
                    // Vérifier que l'annexe existe réellement
                    if (! Annexe::whereKey($activeAnnexeId)->exists()) {
                        // Au lieu de retourner 404 (qui expose des infos), retourner 403
                        // Le header peut contenir un ID invalide du localStorage
                        return response()->json([
                            'message' => 'Accès refusé à cette annexe',
                            'error' => 'invalid_active_annexe'
                        ], 403);
                    }

                    if (method_exists($user, 'isPlatformAdmin') && $user->isPlatformAdmin()) {
                        $request->attributes->set('active_annexe_id', $activeAnnexeId);
                    } elseif ($user->scope === 'institution') {
                        $request->attributes->set('active_annexe_id', $activeAnnexeId);
                    } else {
                        $hasAccess = $user->annexes()->where('annexes.id', $activeAnnexeId)->exists();

                        if ($hasAccess) {
                            $request->attributes->set('active_annexe_id', $activeAnnexeId);
                        } else {
                            return response()->json([
                                'message' => 'Accès non autorisé à cette annexe',
                                'error' => 'unauthorized_annexe_access'
                            ], 403);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('SetActiveAnnexe middleware error: ' . $e->getMessage());
                }
            }
        }

        return $next($request);
    }
}
