<?php

/*
 * This file is part of the Synergy package.
 *
 * Copyright (c) 2015-2016 Synergy.
 *
 * @author Maksim Karpychev <mkarpychev@synergy.ru>
 */

namespace App\Http\Middleware;

use Closure;

class JsonApiMiddleware
{
    public static $PARSED_METHODS = [
        'POST', 'PATCH', 'PUT',
    ];

/**
 * Handle an incoming request.
 *
 * @param \Illuminate\Http\Request $request
 * @param \Closure                 $next
 *
 * @return mixed
 */
    public function handle($request, Closure $next)
    {
        if (in_array($request->getMethod(), self::$PARSED_METHODS, true)) {
            $request->merge((array) json_decode($request->getContent(), true));
        }

        return $next($request);
    }
}
