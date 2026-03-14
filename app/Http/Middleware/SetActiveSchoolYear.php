<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetActiveSchoolYear
{
    /**
     * Injecte l'année scolaire active (header) dans la requête si absente.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $headerYear = trim((string) $request->header('X-Active-School-Year', ''));

        if ($this->isValidSchoolYear($headerYear)) {
            // Exposer pour usage éventuel dans les contrôleurs
            $request->attributes->set('active_school_year', $headerYear);

            // Si le paramètre n'est pas explicitement fourni, on le propage
            if (!$request->filled('school_year')) {
                $request->merge(['school_year' => $headerYear]);
            }
        }

        return $next($request);
    }

    private function isValidSchoolYear(?string $value): bool
    {
        if (!$value || !preg_match('/^(\d{4})-(\d{4})$/', $value, $m)) {
            return false;
        }

        return ((int) $m[2]) === ((int) $m[1] + 1);
    }
}
