<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SoloAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->tieneRol(['ADMIN'])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        return $next($request);
    }
}