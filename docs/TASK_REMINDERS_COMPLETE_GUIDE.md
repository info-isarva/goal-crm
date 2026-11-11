# Task Reminders Complete Setup & Testing Guide

This guide walks you through setting up task reminders so that lead-related tasks automatically generate email and in-app notifications at the configured reminder time.

## Overview

When a task is created or updated with a `due_at` time:
1. A `TaskReminder` record is created 30 minutes before the `due_at` (configurable per-task via the UI).
2. Every minute, the `task:process-reminders` command is scheduled to run (via Laravel Scheduler).
3. The command finds all reminders where `remind_at <= now` and sends:
   - **In-app notifications** (created in the `notifications` table) — displayed immediately in the UI, no queue worker needed.
   - **Emails** — either queued (requires queue worker) or sent immediately if `REMINDER_FORCE_SEND_NOW=true`.

## Prerequisites

### 1. PHP Version

The project requires PHP >= 8.2. Check your CLI PHP version:
```bash
php --version
```

If you see PHP 8.0 or earlier, you need to use a suitable CLI binary. Options:
- Use `php8.2` or `php8.3` explicitly (if installed).
- Update the system `php` symlink to point to PHP 8.2+.
- Install PHP 8.2 (see "Install PHP 8.2" section below).

### 2. Database

Ensure migrations have been run to create the `task_reminders` and `notifications` tables:
```bash
# Check migration status
php artisan migrate:status

# Run pending migrations (use --force in production)
php artisan migrate --force
```

### 3. Scheduler

The Laravel Scheduler must run every minute. Add a cron entry (on the web server host):
```bash
# Edit the crontab
crontab -e

# Add this line (adjust paths and PHP binary as needed)
* * * * * cd /home/crm-demo.isarva.in/public_html && /usr/bin/php8.2 artisan schedule:run >> /dev/null 2>&1
```

Or use a systemd timer (see "Production Setup with Systemd" below).

### 4. Queue Worker (Optional but Recommended for Emails)

If `REMINDER_FORCE_SEND_NOW=false` (the default), emails are queued and require a running queue worker:
```bash
# Run in foreground (for testing)
php artisan queue:work database --sleep=3 --tries=3

# Or run in background with systemd (see "Production Setup" below)
```

If you don't want to run a queue worker (e.g., small deployment), set `REMINDER_FORCE_SEND_NOW=true` to send emails immediately.

## Configuration

### Environment Variables

In `.env`, set:

```dotenv
# Default type: 'email', 'notification', or 'both'
REMINDER_DEFAULT_TYPE=email

# If true, send reminder emails immediately (synchronous).
# If false (default), queue emails for the queue worker.
REMINDER_FORCE_SEND_NOW=false
```

**Choose one:**
- **Development/Testing (no queue worker):** Set `REMINDER_FORCE_SEND_NOW=true` so emails send immediately.
- **Production:** Set `REMINDER_FORCE_SEND_NOW=false` and run a persistent queue worker via systemd/supervisor.

### Per-Task Reminder Offset

When editing a task, users can set a custom reminder offset (in minutes before due time):
- UI field: "Reminder" (select box in task form)
- Default: 30 minutes

## Quick Test (Development)

### Step 1: Set Up for Immediate Testing

```bash
# Use a PHP 8.2+ binary (replace `php` if needed)
export PHP=/usr/bin/php8.2

# Ensure migrations are run
$PHP artisan migrate --force

# Set flag to send emails immediately (so no queue worker needed)
# Edit .env: REMINDER_FORCE_SEND_NOW=true
nano .env
```

### Step 2: Create a Test Task

Create a task with `due_at` a few minutes from now:

```bash
# Use artisan tinker
$PHP artisan tinker
```

Then in the tinker shell:
```php
use App\Models\Task;
use Carbon\Carbon;

// Create a task due 5 minutes from now
$task = Task::create([
    'name' => 'Test Reminder Task ' . time(),
    'description' => 'Testing automatic reminder generation',
    'due_at' => Carbon::now()->addMinutes(5),
    'related_type' => 'lead',
    'related_id' => 1,  // Use an actual lead ID
    'user_owner_id' => 1,  // Use an actual user ID
    'user_assigned_id' => 1,
    'priority' => 'm',
    'status' => 'pending',
    'created_by' => 1,
]);

echo "Task created: {$task->id}, Due at: {$task->due_at}\n";

// Check if reminder was created
$reminders = $task->reminders()->get();
foreach ($reminders as $r) {
    echo "Reminder {$r->id}: remind_at = {$r->remind_at}, type = {$r->reminder_type}\n";
}

exit;
```

### Step 3: Wait for Reminder Time or Run Manually

Either:
- Wait for the scheduled time (the scheduler runs every minute), or
- Run the processor manually:
```bash
$PHP artisan task:process-reminders
```

### Alternative: Trigger Reminders via Secure HTTP Endpoint

If you cannot edit the local crontab (no sudo) or prefer to trigger the job from an external scheduler, the app exposes a token-protected HTTP endpoint you can call to run the reminders processor:

- Endpoint: `POST /internal/run-reminders`
- Header: `X-INTERNAL-RUN-TOKEN: <your-token>`
- Or query param: `?token=<your-token>`

Example curl (replace `<token>`):

```bash
curl -X POST "https://crm-demo.isarva.in/internal/run-reminders" \
    -H "X-INTERNAL-RUN-TOKEN: <token>"
```

To use this, set a token in your `.env`:

```dotenv
INTERNAL_RUN_TOKEN=replace_with_a_long_random_value
```

Then schedule the curl from any external cron service or monitoring service (cron-job.org, GitHub Actions, server control panel, etc.). This avoids needing `sudo` on the host to add a system cron.


### Step 4: Verify

Check the database:
```bash
# In MySQL/MariaDB shell
mysql -u your_user -p your_db

# Check if reminders were processed
SELECT * FROM task_reminders WHERE task_id = YOUR_TASK_ID ORDER BY created_at DESC LIMIT 5;

# Check if notifications were created
SELECT * FROM notifications WHERE notifiable_id = YOUR_USER_ID ORDER BY created_at DESC LIMIT 5;

# Check if emails were queued (if REMINDER_FORCE_SEND_NOW=false)
SELECT * FROM jobs ORDER BY created_at DESC LIMIT 5;
```

Or use the debug command:
```bash
$PHP artisan debug:notifications
```

## Production Setup with Systemd

### 1. Install PHP 8.2 (if needed)

On Ubuntu/Debian:
```bash
sudo apt update
sudo apt install -y php8.2-cli php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-bcmath php8.2-curl
```

On CentOS/RHEL:
```bash
sudo yum install -y php82-cli php82-fpm php82-mysql php82-mbstring php82-xml php82-bcmath php82-curl
```

### 2. Create Scheduler Timer

Create `/etc/systemd/system/isarva-scheduler.timer`:
```ini
[Unit]
Description=Isarva CRM Laravel Scheduler Timer
After=network.target

[Timer]
# Run every minute
OnBootSec=0min
OnUnitActiveSec=1min
Persistent=true

[Install]
WantedBy=timers.target
```

Create `/etc/systemd/system/isarva-scheduler.service`:
```ini
[Unit]
Description=Isarva CRM Laravel Scheduler
After=network.target

[Service]
Type=oneshot
User=www-data
Group=www-data
WorkingDirectory=/home/crm-demo.isarva.in/public_html
ExecStart=/usr/bin/php8.2 artisan schedule:run
```

Enable and start:
```bash
sudo systemctl daemon-reload
sudo systemctl enable --now isarva-scheduler.timer

# Check status
sudo systemctl status isarva-scheduler.timer
sudo systemctl list-timers isarva-scheduler.timer
```

### 3. Create Queue Worker Service

Create `/etc/systemd/system/isarva-queue.service`:
```ini
[Unit]
Description=Isarva CRM Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/home/crm-demo.isarva.in/public_html
Restart=always
RestartSec=5
ExecStart=/usr/bin/php8.2 artisan queue:work database --sleep=3 --tries=3 --timeout=60
StandardOutput=append:/var/log/isarva-queue.log
StandardError=append:/var/log/isarva-queue-error.log

[Install]
WantedBy=multi-user.target
```

Enable and start:
```bash
sudo systemctl daemon-reload
sudo systemctl enable --now isarva-queue.service

# Check status
sudo systemctl status isarva-queue.service
sudo journalctl -u isarva-queue.service -f
```

### 4. Optional: Use Supervisor Instead of Systemd

If you prefer Supervisor, create `/etc/supervisor/conf.d/isarva-queue.conf`:
```ini
[program:isarva-queue]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php8.2 /home/crm-demo.isarva.in/public_html/artisan queue:work database --sleep=3 --tries=3 --timeout=60
autostart=true
autorestart=true
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/isarva-queue.log
user=www-data
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start isarva-queue:*
```

## Troubleshooting

### "No pending reminders to process"
- Ensure `task_reminders` table exists: `php artisan migrate:status`
- Create a task with a `due_at` a few minutes from now.
- Manually run the processor: `php artisan task:process-reminders`

### "Composer detected issues in your platform"
- Your CLI PHP is < 8.2. Use `php8.2` or update your system `php`.

### Emails not being sent
- If `REMINDER_FORCE_SEND_NOW=false`, ensure the queue worker is running: `sudo systemctl status isarva-queue`
- Check mail configuration in `.env` (MAIL_MAILER, MAIL_HOST, MAIL_FROM_ADDRESS, etc.)
- Check `storage/logs/laravel.log` for errors.

### In-app notifications not appearing
- In-app notifications are now created synchronously (don't require a queue worker).
- Check `notifications` table: `SELECT * FROM notifications WHERE notifiable_id = YOUR_USER_ID ORDER BY created_at DESC LIMIT 5;`
- Ensure the notification UI checks the `notifications` table and displays unread ones.

### Logs & Debugging
```bash
# View all recent errors
tail -f /home/crm-demo.isarva.in/public_html/storage/logs/laravel.log

# Run debug command
php artisan debug:notifications

# Run scheduler manually (one-off)
php artisan schedule:run

# Run processor manually (one-off)
php artisan task:process-reminders

# List all scheduled commands
php artisan schedule:list

# Check failed jobs (if queue worker is enabled)
php artisan queue:failed
php artisan queue:retry all
```

## Summary

| Scenario | Setup | Notes |
|----------|-------|-------|
| **Development** | Set `REMINDER_FORCE_SEND_NOW=true`; run scheduler cron | No queue worker needed; emails send synchronously; in-app notifications created immediately |
| **Small Production** | Set `REMINDER_FORCE_SEND_NOW=true`; add cron; no queue worker | Simplest setup; emails sent immediately at reminder time |
| **Full Production** | Set `REMINDER_FORCE_SEND_NOW=false`; add cron & systemd scheduler & queue worker | Best for scale; emails queued and processed by worker; fine-grained control |

---

For questions or issues, check `storage/logs/laravel.log` and run `php artisan debug:notifications` to inspect table state.
