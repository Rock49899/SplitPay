<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\RegistrationController as ApiRegistrationController;

class RegistrationController
{
	/**
	 * Proxy method to delegate to the API RegistrationController.
	 * Keeps route:list / Artisan introspection working while preserving API logic.
	 */
	public function register(Request $request)
	{
		$api = app()->make(ApiRegistrationController::class);
		return $api->register($request);
	}
}
