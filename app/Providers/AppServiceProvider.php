<?php

namespace App\Providers;

use App\Observers\ActivityLogObserver;
use App\Services\EmailService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Backwards-compatibility: define global helper format_amount() if older compiled views
        // or third-party packages still call it. It delegates to the new MoneyFormatter.
        // Ensure the helper function is only declared once globally
        if (! function_exists('format_amount')) {
            function format_amount($amount, $decimals = 2)
            {
                return \App\Helpers\MoneyFormatter::format($amount, $decimals);
            }
        }
        // Money formatting helper and Blade directive
        // Blade directive: @money($value)
        Blade::directive('money', function ($expression) {
            return "<?php echo e(\App\Helpers\MoneyFormatter::format($expression)); ?>";
        });

        EmailService::applyConfiguration();

        $this->registerActivityLogObservers();
    }

    /**
     * Attach ActivityLogObserver to every Eloquent model under app/Models
     * (except those listed in config/audit.php exclude_models).
     */
    protected function registerActivityLogObservers(): void
    {
        $exclude = config('audit.exclude_models', []);
        $modelsPath = app_path('Models');

        if (!is_dir($modelsPath)) {
            return;
        }

        foreach (glob($modelsPath . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
            $class = 'App\\Models\\' . basename($file, '.php');

            if (!class_exists($class)) {
                continue;
            }

            if (in_array($class, $exclude, true)) {
                continue;
            }

            if (!is_subclass_of($class, Model::class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);
            if ($reflection->isAbstract()) {
                continue;
            }

            $class::observe(ActivityLogObserver::class);
        }
    }
}
