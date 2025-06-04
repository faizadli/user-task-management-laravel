<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckUserStatus
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->status) {
            return response()->json(['message' => 'Inactive user'], 403);
        }

        return $next($request);
    }
}