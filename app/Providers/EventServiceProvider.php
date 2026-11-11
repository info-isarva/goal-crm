<?php
namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // ...existing event listeners...
    ];

    public function boot(): void
    {
        parent::boot();
        // Register the activity event subscriber
        \Event::subscribe(\App\Listeners\ActivityEventSubscriber::class);
    }
}
