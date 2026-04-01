<?php

use CleaniqueCoders\MailHistory\Http\Controllers\TrackingController;
use CleaniqueCoders\MailHistory\Models\MailHistory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

beforeEach(function () {
    Route::prefix('mailhistory/track')->group(function () {
        Route::get('click/{hash}', [TrackingController::class, 'click'])->name('mailhistory.tracking.click');
    });
});

it('redirects to original URL after recording click', function () {
    $hash = sha1('click-test');
    $targetUrl = 'https://example.com/page';

    MailHistory::create([
        'uuid' => Str::orderedUuid(),
        'hash' => $hash,
        'status' => 'Sent',
        'headers' => [],
        'body' => 'Test',
        'content' => ['text' => null, 'html' => 'Test'],
    ]);

    $encryptedUrl = Crypt::encryptString($targetUrl);

    $response = $this->get("/mailhistory/track/click/{$hash}?url={$encryptedUrl}");

    $response->assertRedirect($targetUrl);

    $this->assertDatabaseHas('mail_history_events', [
        'type' => 'clicked',
        'url' => $targetUrl,
    ]);

    $this->assertDatabaseHas('mail_histories', [
        'hash' => $hash,
        'status' => 'Clicked',
    ]);
});

it('returns 400 when URL parameter is missing', function () {
    $response = $this->get('/mailhistory/track/click/some-hash');

    $response->assertStatus(400);
});

it('returns 400 when URL parameter is invalid', function () {
    $response = $this->get('/mailhistory/track/click/some-hash?url=invalid-encrypted-data');

    $response->assertStatus(400);
});
