<?php
namespace App\Listeners;

use App\Helpers\ActivityLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\PasswordReset;

class ActivityEventSubscriber
{
    public function handleLogin(Login $event)
    {
        ActivityLogger::log([
            'type' => 'login',
            'module' => 'User',
            'action' => 'login',
            'user_id' => $event->user->id,
        ]);
    }

    public function handleLogout(Logout $event)
    {
        ActivityLogger::log([
            'type' => 'logout',
            'module' => 'User',
            'action' => 'logout',
            'user_id' => $event->user->id ?? null,
        ]);
    }

    public function handleRegistered(Registered $event)
    {
        ActivityLogger::log([
            'type' => 'register',
            'module' => 'User',
            'action' => 'register',
            'user_id' => $event->user->id,
        ]);
    }

    public function handlePasswordReset(PasswordReset $event)
    {
        ActivityLogger::log([
            'type' => 'password_reset',
            'module' => 'User',
            'action' => 'password_reset',
            'user_id' => $event->user->id,
        ]);
    }

    public function subscribe($events)
    {
        $events->listen(Login::class, [self::class, 'handleLogin']);
        $events->listen(Logout::class, [self::class, 'handleLogout']);
        $events->listen(Registered::class, [self::class, 'handleRegistered']);
        $events->listen(PasswordReset::class, [self::class, 'handlePasswordReset']);
    }
}
