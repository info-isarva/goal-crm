<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;

class LogController extends Controller
{
    public function application()
    {
        $user = auth()->user();
        if (!$user || (int) ($user->crm_role_type ?? -1) !== 0) {
            abort(403, 'Unauthorized. Super Admin only.');
        }
        $logPath = storage_path('logs/laravel.log');
        $logs = File::exists($logPath) ? File::get($logPath) : 'No log file found.';
        // Optionally, limit log size for display
        if (strlen($logs) > 100000) {
            $logs = substr($logs, -100000); // Show last 100KB
        }
        return view('logs.application', compact('logs'));
    }
}
