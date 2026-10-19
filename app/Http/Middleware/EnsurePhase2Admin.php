<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePhase2Admin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        abort_unless($user && $user->status === 'active' && $user->hasAnyRole(['super_admin', 'admin']), 403);

        return $next($request);
    }
}
