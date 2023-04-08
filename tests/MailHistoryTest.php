<?php

use CleaniqueCoders\MailHistory\Tests\Mail\WelcomeMail;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Artisan::call('vendor:publish', [
        '--tag' => 'mailhistory-migrations',
        '--force' => true,
    ]);
    Artisan::call('migrate:fresh');
});

it('can store sent email in database', function ($email) {
    Mail::fake();
    // assert email sending / sent

    Mail::to($email)
        ->send(new WelcomeMail);

    Mail::assertSent(WelcomeMail::class);

    // $this->assertDatabaseCount('mail_histories', 2);
})->with([
    'enunomaduro@gmail.com',
    'nasrul@cleaniquecoders.com',
]);
