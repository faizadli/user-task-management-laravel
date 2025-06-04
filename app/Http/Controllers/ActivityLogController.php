<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        // Remove manual check - let the policy handle it
        $this->authorize('viewAny', ActivityLog::class);
        
        $logs = ActivityLog::with('user')
                          ->orderBy('logged_at', 'desc')
                          ->paginate(50);
                          
        return response()->json([
            'data' => $logs->items()
        ]);
    }
}