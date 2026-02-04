<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthController as ApiAuthController;

class AuthController
{
	// POST /api/admin/login
	public function login(Request $request)
	{
		$api = app()->make(ApiAuthController::class);
		return $api->login($request);
	}

	// POST /api/admin/logout
	public function logout(Request $request)
	{
		$api = app()->make(ApiAuthController::class);
		return $api->logout($request);
	}

	// GET /api/admin/me
	public function me(Request $request)
	{
		$api = app()->make(ApiAuthController::class);
		return $api->me($request);
	}
}
