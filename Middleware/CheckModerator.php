<?php

namespace App\Http\Middleware;

use Closure;

class CheckModerator
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
        if(!($request->user()->isModerator()))
            return response()->json([ 'message' => trans('global.invalid_credentials')],401);
        return $next($request);
    }
}
