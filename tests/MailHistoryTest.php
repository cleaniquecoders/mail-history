<?php

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

    Event::fake();

    foreach (config('mailhistory.events') as $event => $listeners) {
        Event::flush($event);
        Event::forget($event);
    }

    $this->assertFalse(
        Event::hasListeners(MessageSending::class),
    );

    $this->assertFalse(
        Event::hasListeners(MessageSent::class),
    );
})->skip('Need to work on disabling the event listener at runtime.');
