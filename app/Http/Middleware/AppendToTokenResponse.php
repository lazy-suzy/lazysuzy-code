<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class AppendToTokenResponse
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
        // get response of the API request first /api/oauth/token
        $response =  $next($request);

        $content = json_decode($response->content(), true);

        if (!empty($content['access_token'])) {

            $resp['success'] = [
                'token' => $content['access_token'],
            ];
            $resp['user'] = Auth::user();
            $response->setContent($resp);
        }

        return $response;
    }
}
