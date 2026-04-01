<?php

namespace CleaniqueCoders\MailHistory;

use CleaniqueCoders\MailHistory\Commands\MailHistoryCommand;
use CleaniqueCoders\MailHistory\Commands\MailHistoryPruneCommand;
use CleaniqueCoders\MailHistory\Commands\MailHistoryStatsCommand;
use CleaniqueCoders\MailHistory\Commands\MailHistoryTestCommand;
use CleaniqueCoders\MailHistory\Commands\MailHistoryTestWebhookCommand;
use CleaniqueCoders\MailHistory\Http\Controllers\TrackingController;
use CleaniqueCoders\MailHistory\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MailHistoryServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('mailhistory')
            ->hasConfigFile()
            ->hasConsoleCommands(
                MailHistoryCommand::class,
                MailHistoryTestCommand::class,
                MailHistoryStatsCommand::class,
                MailHistoryPruneCommand::class,
                MailHistoryTestWebhookCommand::class,
            )
            ->hasMigration('create_mailhistory_table')
            ->hasMigration('create_mail_history_events_table');
    }

    public function packageRegistered()
    {
        if (! config('mailhistory.enabled')) {
            return;
        }

        foreach (config('mailhistory.events') as $event => $listeners) {
            foreach (array_unique($listeners, SORT_REGULAR) as $listener) {
                Event::listen($event, $listener);
            }
        }
    }

    public function packageBooted()
    {
        $this->registerWebhookRoutes();
        $this->registerTrackingRoutes();
    }

    protected function registerWebhookRoutes(): void
    {
        if (! config('mailhistory.webhooks.enabled', false)) {
            return;
        }

        Route::prefix(config('mailhistory.webhooks.path', 'mailhistory/webhooks'))
            ->middleware(config('mailhistory.webhooks.middleware', []))
            ->group(function () {
                Route::post('{provider}', WebhookController::class)
                    ->name('mailhistory.webhooks');
            });
    }

    protected function registerTrackingRoutes(): void
    {
        $openEnabled = config('mailhistory.tracking.open.enabled', false);
        $clickEnabled = config('mailhistory.tracking.click.enabled', false);

        if (! $openEnabled && ! $clickEnabled) {
            return;
        }

        Route::prefix(config('mailhistory.tracking.path', 'mailhistory/track'))
            ->middleware(config('mailhistory.tracking.middleware', []))
            ->group(function () use ($openEnabled, $clickEnabled) {
                if ($openEnabled) {
                    Route::get('open/{hash}', [TrackingController::class, 'open'])
                        ->name('mailhistory.tracking.open');
                }

                if ($clickEnabled) {
                    Route::get('click/{hash}', [TrackingController::class, 'click'])
                        ->name('mailhistory.tracking.click');
                }
            });
    }
}
