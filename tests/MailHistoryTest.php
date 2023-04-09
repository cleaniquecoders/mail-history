<?php

use CleaniqueCoders\MailHistory\Contracts\HashContract;
use CleaniqueCoders\MailHistory\Exceptions\MailHistoryException;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    config()->set('view.paths', [
        dirname(__FILE__).'/resources/views',
    ]);

    Artisan::call('vendor:publish', [
        '--tag' => 'mailhistory-migrations',
        '--force' => true,
    ]);
    Artisan::call('migrate:fresh');
});

it('has HashContract', function () {
    $this->assertTrue(
        file_exists(dirname(__FILE__).'/../src/Contracts/HashContract.php')
    );

    $this->assertTrue(
        in_array(HashContract::class, class_implements(config('mailhistory.model')))
    );

    $this->assertTrue(
        class_exists(MailHistoryException::class)
    );

    $this->assertTrue(
        method_exists(
            MailHistoryException::class,
            'throwIfHashContractMissing'
        )
    );
});

it('has MessageSending and MessageSent event listened to if the package is enabled', function () {
    $this->assertTrue(
        Event::hasListeners(MessageSending::class),
    );

    $this->assertTrue(
        Event::hasListeners(MessageSent::class),
    );
});

it('does not has the MessageSending and MessageSent event listened to if the package is disabled', function () {

    config([
        'mailhistory.enabled' => false,
    ]);

    $this->assertTrue(
        Event::hasListeners(MessageSending::class),
    );

    $this->assertTrue(
        Event::hasListeners(MessageSent::class),
    );
});
