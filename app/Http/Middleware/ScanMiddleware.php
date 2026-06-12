<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class ScanMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route("login")->with("error","Not logged in");
        }

        if (Auth::user()->role !== 'scan' && !Auth::user()->is_admin) {
            abort(403, "Not have privilage");
        }
        
        return $next($request);
    }
}
