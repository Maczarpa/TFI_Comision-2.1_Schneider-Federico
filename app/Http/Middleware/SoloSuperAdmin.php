<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SoloSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !in_array('SUPER_ADMIN', $user->roles_slug)) {
            return response()->json(['error' => 'Acceso exclusivo para Super Administrador'], 403);
        }

        return $next($request);
    }
}