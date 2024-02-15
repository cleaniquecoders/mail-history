<?php

namespace CleaniqueCoders\MailHistory;

use CleaniqueCoders\MailHistory\Commands\MailHistoryCommand;
use CleaniqueCoders\MailHistory\Commands\MailHistoryTestCommand;
use Illuminate\Support\Facades\Event;
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
                MailHistoryCommand::class, MailHistoryTestCommand::class
            )
            ->hasMigration('create_mailhistory_table');
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
}
