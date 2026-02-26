<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
	/**
	 * The URIs that should be excluded from CSRF verification.
	 *
	 * @var array<int, string>
	 */
	protected $except = [
		// Exclude API routes (stateless) to avoid CSRF 419 for API clients
		'api/*',
		'api/payplus/webhook', 
	];
}


