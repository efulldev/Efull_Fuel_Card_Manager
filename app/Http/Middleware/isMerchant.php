<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class isMerchant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
        { if (Auth::user() &&  Auth::user()->isMerchant()) {
            return $next($request);
        }

        return response('Unauthorized.', 401);
    }
}
