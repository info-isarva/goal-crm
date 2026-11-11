<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class InternalController extends Controller
{
    /**
     * Trigger internal commands (protected by token).
     * POST /internal/run-reminders
     * Headers: X-INTERNAL-RUN-TOKEN: <token> OR ?token=<token>
     */
    public function runReminders(Request $request)
    {
        $token = $request->header('X-INTERNAL-RUN-TOKEN') ?? $request->query('token');
        $expected = env('INTERNAL_RUN_TOKEN');

        if (empty($expected) || !$token || !hash_equals((string) $expected, (string) $token)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Run the reminders processor and capture output
        try {
            Artisan::call('task:process-reminders');
            $output = trim(Artisan::output());
            return response()->json(['ok' => true, 'output' => $output]);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
