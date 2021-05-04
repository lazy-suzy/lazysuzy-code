<?php

namespace App\Http\Middleware;

use App\Models\UserVisits;
use Closure;
use Auth;

class UserVisit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $sku = $request->route('sku');
        $user_id = Auth::user()->id;

        UserVisits::save_user_visit_sku($user_id, $sku);
        return $next($request);
    }
}
