<?php

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

it('has MessageSending and MessageSent event listened to if the package is enabled', function () {
    $this->assertTrue(
        Event::hasListeners(MessageSending::class)
    );

    $this->assertTrue(
        Event::hasListeners(MessageSent::class)
    );
});

it('does not have the MessageSending and MessageSent event listened when disabled', function () {
    config([
        'mailhistory.enabled' => false,
    ]);

    foreach (config('mailhistory.events') as $event => $listeners) {
        Event::flush($event);
        Event::forget($event);
    }

    $this->assertFalse(
        Event::hasListeners(MessageSending::class)
    );

    $this->assertFalse(
        Event::hasListeners(MessageSent::class)
    );
});

it('stores mail history when sending mail', function () {
    $mailable = new \Illuminate\Mail\Mailable;
    $mailable->to('test@example.com')
        ->from('from@example.com')
        ->subject('Test Subject')
        ->html('Test content');

    Mail::send($mailable);

    $this->assertDatabaseHas('mail_histories', [
        'status' => 'Sending',
        'body' => 'Test content',
        'content' => '{"text":null,"text-charset":null,"html":"Test content","html-charset":"utf-8"}',
        'meta' => '{"origin":"Mail"}',
    ]);
});
