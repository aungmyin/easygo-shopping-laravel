<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminWebMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            return redirect()->route('admin.login');
        }
        return $next($request);
    }
}
