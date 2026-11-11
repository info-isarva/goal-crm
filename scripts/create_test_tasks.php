<?php
// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Task;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use Carbon\Carbon;

$deal = Deal::first();
$related_type = $deal ? 'deal' : 'lead';
$related_id = $deal ? $deal->id : (Lead::first()->id ?? 1);
$user = User::first();
if (! $user) {
    echo "No users found in DB. Aborting.\n";
    exit(1);
}
$due = Carbon::now()->addDay();

// Task with reminders enabled
$t1 = Task::create([
    'name' => 'Test Reminder Enabled ' . uniqid(),
    'description' => 'T1',
    'due_at' => $due->format('Y-m-d H:i:s'),
    'related_type' => $related_type,
    'related_id' => $related_id,
    'priority' => 'normal',
    'status' => 'Not Started',
    'user_owner_id' => $user->id,
    'created_by' => $user->id,
    'reminder_notifications_enabled' => true,
]);

// Task with reminders disabled
$t2 = Task::create([
    'name' => 'Test Reminder Disabled ' . uniqid(),
    'description' => 'T2',
    'due_at' => $due->copy()->addDay()->format('Y-m-d H:i:s'),
    'related_type' => $related_type,
    'related_id' => $related_id,
    'priority' => 'normal',
    'status' => 'Not Started',
    'user_owner_id' => $user->id,
    'created_by' => $user->id,
    'reminder_notifications_enabled' => false,
]);

// Refresh relationships
$t1->load('reminders');
$t2->load('reminders');

echo "Created t1 id={$t1->id}, reminders_count=" . $t1->reminders->count() . "\n";
echo "Created t2 id={$t2->id}, reminders_count=" . $t2->reminders->count() . "\n";

// Show first reminder time for t1 if any
if ($t1->reminders->count()) {
    $r = $t1->reminders->first();
    echo "t1 first reminder at: " . ($r->remind_at instanceof Carbon ? $r->remind_at->toDateTimeString() : $r->remind_at) . "\n";
}

exit(0);
