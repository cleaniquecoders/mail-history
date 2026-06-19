<?php

namespace CleaniqueCoders\MailHistory;

use CleaniqueCoders\MailHistory\Actions\Contracts\MailHistoryReport;
use CleaniqueCoders\MailHistory\Actions\GetMailHistoryReport;
use CleaniqueCoders\MailHistory\Commands\MailHistoryCommand;
use CleaniqueCoders\MailHistory\Commands\MailHistoryPruneCommand;
use CleaniqueCoders\MailHistory\Commands\MailHistoryStatsCommand;
use CleaniqueCoders\MailHistory\Commands\MailHistoryTestCommand;
use CleaniqueCoders\MailHistory\Commands\MailHistoryTestWebhookCommand;
use CleaniqueCoders\MailHistory\Http\Controllers\TrackingController;
use CleaniqueCoders\MailHistory\Http\Controllers\WebhookController;
use CleaniqueCoders\MailHistory\Listeners\EnsureMailMetadataHash;
use CleaniqueCoders\MailHistory\Listeners\InjectMailTracking;
use CleaniqueCoders\MailHistory\Livewire\Dashboard;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
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
            ->hasViews()
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
        $this->app->bind(
            MailHistoryReport::class,
            config('mailhistory.report', GetMailHistoryReport::class)
        );

        if (! config('mailhistory.enabled')) {
            return;
        }

        // Ensure every outgoing message carries an X-Metadata-hash header BEFORE
        // the store listeners run, so MessageSent correlation + open/click
        // tracking work for ALL mail — not just Mailables using
        // InteractsWithMailMetadata. Registered here (not via the publishable
        // `events` config) so upgrading the package needs no config re-publish.
        Event::listen(MessageSending::class, EnsureMailMetadataHash::class);

        foreach (config('mailhistory.events') as $event => $listeners) {
            foreach (array_unique($listeners, SORT_REGULAR) as $listener) {
                Event::listen($event, $listener);
            }
        }

        // Auto-inject the open pixel + rewrite links AFTER the row is stored, so
        // the stored body stays original and tracking applies only to what is
        // actually sent. Runs only when self-hosted tracking is enabled.
        if (config('mailhistory.tracking.open.enabled') || config('mailhistory.tracking.click.enabled')) {
            Event::listen(MessageSending::class, InjectMailTracking::class);
        }
    }

    public function packageBooted()
    {
        $this->registerDashboardRoutes();
        $this->registerLivewireComponents();
        $this->registerWebhookRoutes();
        $this->registerTrackingRoutes();
    }

    protected function registerDashboardRoutes(): void
    {
        if (! config('mailhistory.ui.enabled', false)) {
            return;
        }

        Route::group([
            'prefix' => config('mailhistory.ui.prefix', 'mailhistory'),
            'middleware' => config('mailhistory.ui.middleware', ['web', 'auth']),
            'as' => config('mailhistory.ui.name', 'mailhistory.'),
        ], function () {
            Route::get('/', function () {
                return view('mailhistory::index');
            })->name('dashboard');
        });
    }

    protected function registerLivewireComponents(): void
    {
        if (! config('mailhistory.ui.enabled', false)) {
            return;
        }

        if (! class_exists(Livewire::class)) {
            return;
        }

        if (method_exists(app('livewire'), 'addNamespace')) {
            // Livewire 4
            Livewire::addNamespace(
                'mailhistory',
                null,
                'CleaniqueCoders\\MailHistory\\Livewire',
                __DIR__.'/Livewire',
                __DIR__.'/../resources/views/livewire',
            );
        } else {
            // Livewire 3
            Livewire::component('mailhistory::dashboard', Dashboard::class);
        }
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
