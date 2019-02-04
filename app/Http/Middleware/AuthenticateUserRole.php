<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class AuthenticateUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $role)
    {
        $role = explode('|', $role);
        if ($request->user() && $request->user()->hasAnyRole($role)) {
            return $next($request);
        }

        Auth::logout();
        abort(403, 'Unauthorized action.');
    }
}
