<?php

namespace App\Http\Middleware;

use App\Models\SchoolYear;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceSchoolYearAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $selectedYear = $request->input('school_year') ?: $request->attributes->get('active_school_year');
        $explicitSchoolYear = $request->query->has('school_year') || $request->request->has('school_year');

        $request->attributes->set('requested_school_year', $selectedYear);
        $request->attributes->set('effective_school_year', $selectedYear);

        // Par défaut: aucun blocage si aucune année explicitement sélectionnée
        $request->attributes->set('school_year_available', true);
        $request->attributes->set('school_year_read_only', false);

        if (!$selectedYear) {
            $response = $next($request);

            return $this->withSchoolYearHeaders($response, $request);
        }

        $schoolYear = SchoolYear::where('year', $selectedYear)->first();

        // Année inconnue = pas de données + interdit en écriture
        if (!$schoolYear) {
            // Compatibilité: si l'année vient uniquement du header (localStorage obsolète),
            // on bascule vers la première année valide (active puis closed) pour éviter l'UI vide.
            if (!$explicitSchoolYear) {
                $fallback = SchoolYear::query()
                    ->whereIn('status', ['active', 'closed'])
                    ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
                    ->orderByDesc('year')
                    ->first();

                if ($fallback) {
                    $request->merge(['school_year' => $fallback->year]);
                    $request->attributes->set('active_school_year', $fallback->year);
                    $request->attributes->set('effective_school_year', $fallback->year);
                    $schoolYear = $fallback;
                }
            }
        }

        if (!$schoolYear) {
            $request->attributes->set('school_year_available', false);
            $request->attributes->set('school_year_read_only', true);

            if (!$request->isMethod('GET') && !$request->isMethod('HEAD') && !$request->isMethod('OPTIONS')) {
                return response()->json([
                    'message' => "L'année scolaire {$selectedYear} n'est pas validée sur la plateforme.",
                    'error' => 'school_year_not_available',
                ], 423);
            }

            $response = $next($request);

            return $this->withSchoolYearHeaders($response, $request);
        }

        $request->attributes->set('effective_school_year', $schoolYear->year);

        // draft = pas encore ouverte (pas de données exposées)
        if ($schoolYear->status === 'draft') {
            $request->attributes->set('school_year_available', false);
            $request->attributes->set('school_year_read_only', true);
        }

        // closed = données consultables, mais verrouillées
        if ($schoolYear->status === 'closed') {
            $request->attributes->set('school_year_available', true);
            $request->attributes->set('school_year_read_only', true);
        }

        // active = tout autorisé
        if ($schoolYear->status === 'active') {
            $request->attributes->set('school_year_available', true);
            $request->attributes->set('school_year_read_only', false);
        }

        if ($request->attributes->get('school_year_read_only') === true
            && !$request->isMethod('GET')
            && !$request->isMethod('HEAD')
            && !$request->isMethod('OPTIONS')) {
            return response()->json([
                'message' => "L'année scolaire {$selectedYear} est en lecture seule.",
                'error' => 'school_year_read_only',
            ], 423);
        }

        $response = $next($request);

        return $this->withSchoolYearHeaders($response, $request);
    }

    private function withSchoolYearHeaders(Response $response, Request $request): Response
    {
        $requested = $request->attributes->get('requested_school_year');
        $effective = $request->attributes->get('effective_school_year') ?: $request->input('school_year');
        $available = $request->attributes->get('school_year_available') ? '1' : '0';
        $readOnly = $request->attributes->get('school_year_read_only') ? '1' : '0';

        if (!empty($requested)) {
            $response->headers->set('X-Requested-School-Year', (string) $requested);
        }

        if (!empty($effective)) {
            $response->headers->set('X-Effective-School-Year', (string) $effective);
        }

        $response->headers->set('X-School-Year-Available', $available);
        $response->headers->set('X-School-Year-Read-Only', $readOnly);

        return $response;
    }
}
