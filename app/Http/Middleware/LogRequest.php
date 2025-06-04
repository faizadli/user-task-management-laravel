<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRequest
{
    public function handle(Request $request, Closure $next)
    {   
        try {
            $response = $next($request);
            
            // Gunakan channel api_activity khusus
            Log::channel('api_activity')->info('API Request', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'user' => $request->user() ? $request->user()->id : 'guest',
                'ip' => $request->ip(),
                'status' => $response ? $response->getStatusCode() : 'unknown'
            ]);
            
            return $response;
        } catch (\Throwable $e) {
            Log::channel('api_activity')->error('Error logging request: ' . $e->getMessage());
            throw $e;
        }
    }
}