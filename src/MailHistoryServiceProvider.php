<?php

namespace CleaniqueCoders\MailHistory;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use CleaniqueCoders\MailHistory\Commands\MailHistoryCommand;
use Illuminate\Support\Facades\Event;

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
            ->hasMigration('create_mailhistory_table');
    }

    public function packageRegistered()
    {
        foreach (config('mailhistory.events') as $event => $listeners) {
            foreach (array_unique($listeners, SORT_REGULAR) as $listener) {
                Event::listen($event, $listener);
            }
        }
    }
}
