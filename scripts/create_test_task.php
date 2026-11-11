<?php
// One-off script to create a test Task via Eloquent so Task::boot runs and a TaskReminder is created.
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Task;
use Carbon\Carbon;

$ownerId = (int)($argv[1] ?? 6); // default to user id 6 (ashoka@isarvait.com)
$minutesFromNow = (int)($argv[2] ?? 2);

$dueAt = Carbon::now()->addMinutes($minutesFromNow)->format('Y-m-d H:i:00');

$task = Task::create([
    'name' => 'Test Reminder ' . time(),
    'description' => 'Automated test task for reminders',
    'due_at' => $dueAt,
    'related_type' => 'lead',
    'related_id' => 1,
    'user_owner_id' => $ownerId,
    'user_assigned_id' => $ownerId,
    'priority' => 'normal',
    'status' => 'Not Started',
    'created_by' => $ownerId,
]);

echo "Created task id: {$task->id}\n";

// Show the created reminder
$reminders = $task->reminders()->get();
foreach ($reminders as $r) {
    echo "Reminder id: {$r->id}, remind_at: {$r->remind_at}, type: {$r->reminder_type}\n";
}
