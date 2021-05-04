<?php

namespace App\Http\Middleware;

use Auth;
use Closure;

class CheckUserType
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
        $user = Auth::user();
        if($user != null && $user->user_type != config('user.user_type.admin'))
            return response([
                'status' => 'Unauthorised',
                'message' => 'User not admin. Please login via admin creds.'
            ], 401);
        
        return $next($request);
    }
}
