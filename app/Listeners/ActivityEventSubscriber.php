<?php

namespace App\Listeners;

use App\Helpers\ActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;

class ActivityEventSubscriber
{
    public function handleLogin(Login $event): void
    {
        ActivityLogger::log([
            'type' => 'login',
            'module' => 'User',
            'action' => 'login',
            'user_id' => $event->user->id,
        ]);
    }

    public function handleLogout(Logout $event): void
    {
        ActivityLogger::log([
            'type' => 'logout',
            'module' => 'User',
            'action' => 'logout',
            'user_id' => $event->user->id ?? null,
        ]);
    }

    public function handleRegistered(Registered $event): void
    {
        ActivityLogger::log([
            'type' => 'register',
            'module' => 'User',
            'action' => 'register',
            'user_id' => $event->user->id,
        ]);
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        ActivityLogger::log([
            'type' => 'password_reset',
            'module' => 'User',
            'action' => 'password_reset',
            'user_id' => $event->user->id,
        ]);
    }

    public function handleFailed(Failed $event): void
    {
        ActivityLogger::log([
            'type' => 'login_failed',
            'module' => 'User',
            'action' => 'login_failed',
            'user_id' => $event->user->id ?? null,
            'details' => [
                'email' => $event->credentials['email'] ?? ($event->credentials['username'] ?? null),
                'guard' => $event->guard,
            ],
        ]);
    }

    public function subscribe($events): void
    {
        $events->listen(Login::class, [self::class, 'handleLogin']);
        $events->listen(Logout::class, [self::class, 'handleLogout']);
        $events->listen(Registered::class, [self::class, 'handleRegistered']);
        $events->listen(PasswordReset::class, [self::class, 'handlePasswordReset']);
        $events->listen(Failed::class, [self::class, 'handleFailed']);
    }
}
