<?php
// Script to call TaskController@store directly for testing
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\TaskController;

// Simulate logged-in user
Auth::loginUsingId((int)($argv[1] ?? 6));

$data = [
    'name' => 'Controller Test ' . time(),
    'description' => 'Created via TaskController@store test script',
    'due_at' => date('Y-m-d H:i:00', strtotime('+3 minutes')),
    'related_type' => 'lead',
    'related_id' => 1,
    'priority' => 'normal',
    'status' => 'Not Started',
    'reminder_offset' => 5,
];

$request = Request::create('/tasks', 'POST', $data);

$controller = new TaskController();
$response = $controller->store($request);

// If a redirect response, print target
if ($response instanceof \Illuminate\Http\RedirectResponse) {
    echo "Controller returned redirect to: " . $response->getTargetUrl() . PHP_EOL;
} else {
    echo "Controller returned: ";
    var_export($response);
    echo PHP_EOL;
}

// Find the most recent task created by this user and show reminders
$task = \App\Models\Task::where('created_by', (int)($argv[1] ?? 6))->orderByDesc('id')->first();
if ($task) {
    echo "Created task id: {$task->id}\n";
    foreach ($task->reminders as $r) {
        echo "Reminder id: {$r->id}, remind_at: {$r->remind_at}, type: {$r->reminder_type}\n";
    }
} else {
    echo "No task found for user." . PHP_EOL;
}
