<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Observers\ActivityLogObserver;
use Illuminate\Support\Facades\Blade;
use App\Models\Company;
use App\Models\User;
use App\Models\Deal;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Person;
use App\Models\Lead;
use App\Models\Meeting;
use App\Models\Note;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\Stage;
use App\Models\Task;
use App\Models\TaxRate;
use App\Services\EmailService;

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

    // Register activity logger for all main models
    User::observe(ActivityLogObserver::class);
    Deal::observe(ActivityLogObserver::class);
    Customer::observe(ActivityLogObserver::class);
    Organization::observe(ActivityLogObserver::class);
    Person::observe(ActivityLogObserver::class);
    Lead::observe(ActivityLogObserver::class);
    Meeting::observe(ActivityLogObserver::class);
    Note::observe(ActivityLogObserver::class);
    Product::observe(ActivityLogObserver::class);
    ProductCategory::observe(ActivityLogObserver::class);
    Role::observe(ActivityLogObserver::class);
    Stage::observe(ActivityLogObserver::class);
    Task::observe(ActivityLogObserver::class);
    TaxRate::observe(ActivityLogObserver::class);
    }
}
