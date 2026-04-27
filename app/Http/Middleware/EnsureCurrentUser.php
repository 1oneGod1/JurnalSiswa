<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! current_user_check()) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
