# Reminder Email Automation — Setup Guide

This guide shows how to automatically process and deliver task reminders in this Laravel app.

Summary
- `task:process-reminders` finds pending reminders and queues mails + sends in-app notifications.
- The scheduler must run every minute (cron) to automatically execute the command.
- The queued mails require a running queue worker (systemd or supervisor) to process jobs and send emails.

Files added to this repo
- `deploy/cron/laravel-schedule.cron` — sample crontab line to add.
- `deploy/systemd/laravel-queue-worker.service` — sample `systemd` service unit for a persistent queue worker.

Recommended steps (run as root or a privileged user)

1) Install the cron entry
- Edit the crontab for the deploy/web user (or root) and add the line from `deploy/cron/laravel-schedule.cron`.

Example for root or a system admin:
```bash
# open root crontab
sudo crontab -e
# then paste the single line from deploy/cron/laravel-schedule.cron
```

If you prefer adding it to the `www-data` user (if web files are owned by `www-data`):
```bash
# as root
crontab -u www-data -e
# paste the line
```

2) Install systemd service for the queue worker
(Requires root)
```bash
# copy service file to systemd
sudo cp deploy/systemd/laravel-queue-worker.service /etc/systemd/system/laravel-queue-worker.service

# reload systemd and enable start on boot
sudo systemctl daemon-reload
sudo systemctl enable --now laravel-queue-worker.service

# check status and logs
sudo systemctl status laravel-queue-worker.service
sudo journalctl -u laravel-queue-worker.service -f
```

Notes:
- The service uses `/usr/local/lsws/lsphp82/bin/php` and the project path `/home/crm-demo.isarva.in/public_html`. Update those if your server uses different locations.
- The `User` and `Group` are set to `www-data`. If your server uses a different user (e.g., `isarva`), change both fields accordingly.

3) Verify reminders flow
- Create a test Task with a `due_at` a few minutes ahead and confirm a TaskReminder was created with a `remind_at` before the `due_at`.
- Wait for a minute (or run `php artisan task:process-reminders` manually) and then check `jobs` table for queued mail jobs and `notifications` table for in-app notifications.

Useful commands
```bash
# run scheduler manually
/usr/local/lsws/lsphp82/bin/php /home/crm-demo.isarva.in/public_html/artisan schedule:run

# run reminder command manually
/usr/local/lsws/lsphp82/bin/php /home/crm-demo.isarva.in/public_html/artisan task:process-reminders

# run worker ad-hoc (for testing) - will process until empty
/usr/local/lsws/lsphp82/bin/php /home/crm-demo.isarva.in/public_html/artisan queue:work --stop-when-empty --tries=3 --timeout=60
```

Troubleshooting
- If mail jobs fail, check `storage/logs/laravel.log` and the `failed_jobs` table for detailed exceptions.
- If views fail when sending email (render errors), ensure `resources/views/emails/task-reminder.blade.php` handles nullable fields; I already adjusted due_at formatting.
- If queue worker exits quickly, check `journalctl -u laravel-queue-worker.service` for PHP startup warnings. Missing PHP extensions will appear as startup warnings.

Optional: Supervisor config
If your environment prefers `supervisord`, create a supervisor program that runs the same artisan `queue:work` command. The `systemd` unit is included as a simple, modern alternative.

If you want, I can:
- Install and enable the systemd unit for you (requires sudo/root in this session).
- Create a `supervisord` config file instead.
- Create a small test job run now to confirm end-to-end delivery.

Tell me which of those you'd like me to do next.