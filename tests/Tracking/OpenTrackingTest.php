<?php

use CleaniqueCoders\MailHistory\Http\Controllers\TrackingController;
use CleaniqueCoders\MailHistory\Models\MailHistory;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

beforeEach(function () {
    Route::prefix('mailhistory/track')->group(function () {
        Route::get('open/{hash}', [TrackingController::class, 'open'])->name('mailhistory.tracking.open');
    });
});

it('returns a 1x1 transparent GIF for open tracking', function () {
    $hash = sha1('open-test');

    MailHistory::create([
        'uuid' => Str::orderedUuid(),
        'hash' => $hash,
        'status' => 'Sent',
        'headers' => [],
        'body' => 'Test',
        'content' => ['text' => null, 'html' => 'Test'],
    ]);

    $response = $this->get("/mailhistory/track/open/{$hash}");

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'image/gif');

    expect($response->headers->get('Cache-Control'))->toContain('no-store')
        ->toContain('no-cache')
        ->toContain('max-age=0');
});

it('records opened event when tracking pixel is hit', function () {
    $hash = sha1('open-record');

    MailHistory::create([
        'uuid' => Str::orderedUuid(),
        'hash' => $hash,
        'status' => 'Sent',
        'headers' => [],
        'body' => 'Test',
        'content' => ['text' => null, 'html' => 'Test'],
    ]);

    $this->get("/mailhistory/track/open/{$hash}");

    $this->assertDatabaseHas('mail_history_events', [
        'type' => 'opened',
    ]);

    $this->assertDatabaseHas('mail_histories', [
        'hash' => $hash,
        'status' => 'Opened',
    ]);
});

it('returns GIF even when hash is not found', function () {
    $response = $this->get('/mailhistory/track/open/nonexistent-hash');

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'image/gif');
});
