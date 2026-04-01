<?php

namespace CleaniqueCoders\MailHistory\Tests;

use CleaniqueCoders\MailHistory\MailHistoryServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'CleaniqueCoders\\MailHistory\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            MailHistoryServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        config()->set('view.paths', [
            dirname(__FILE__).'/resources/views',
        ]);

        $migration = include __DIR__.'/../database/migrations/create_mailhistory_table.php.stub';
        $migration->up();

        $eventsMigration = include __DIR__.'/../database/migrations/create_mail_history_events_table.php.stub';
        $eventsMigration->up();
    }
}
