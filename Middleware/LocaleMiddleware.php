<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Session;

class LocaleMiddleware
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
        if($request->wantsJson() && $request->header('accept-language')) {
            $local  =   $request->header('accept-language');
        } else {
            $local = Session::get('local', 'ar');
        }

        $userLang = optional($request->user('sanctum'))->lang;
        if(!is_null($userLang) && $userLang != $local) { $request->user('sanctum')->update(['lang'=>$local]); }

        app()->setLocale($local);
        return $next($request);

    }
}
