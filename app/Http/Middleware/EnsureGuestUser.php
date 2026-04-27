<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuestUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (current_user_check()) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
