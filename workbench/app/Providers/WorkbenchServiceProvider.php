<?php

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Enable the mail history dashboard UI
        config()->set('mailhistory.ui.enabled', true);

        // Allow unauthenticated access for local dev
        config()->set('mailhistory.ui.middleware', ['web']);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
