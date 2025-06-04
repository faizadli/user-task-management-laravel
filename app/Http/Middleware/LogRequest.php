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
            // Tangkap respons dari middleware berikutnya
            $response = $next($request);
            
            // Log permintaan yang berhasil
            Log::info('API Request', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'user' => $request->user() ? $request->user()->id : 'guest',
                'ip' => $request->ip(),
                'status' => $response ? $response->status() : 'unknown'
            ]);
            
            return $response;
        } catch (\Throwable $e) {
            // Log error tanpa mempengaruhi respons
            Log::error('Error logging request: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Lempar kembali exception untuk ditangani oleh handler exception Laravel
            throw $e;
        }
    }
}