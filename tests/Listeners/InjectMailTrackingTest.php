<?php

use CleaniqueCoders\MailHistory\Http\Controllers\TrackingController;
use CleaniqueCoders\MailHistory\Listeners\InjectMailTracking;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Mime\Email;

beforeEach(function () {
    Route::prefix('mailhistory/track')->group(function () {
        Route::get('open/{hash}', [TrackingController::class, 'open'])->name('mailhistory.tracking.open');
        Route::get('click/{hash}', [TrackingController::class, 'click'])->name('mailhistory.tracking.click');
    });
});

function trackedEmail(string $hash): Email
{
    $email = (new Email)->html('<html><body><a href="https://example.com/page">Link</a></body></html>');
    $email->getHeaders()->addTextHeader('X-Metadata-hash', $hash);

    return $email;
}

it('injects the open pixel and rewrites links when tracking is enabled', function () {
    config(['mailhistory.tracking.open.enabled' => true, 'mailhistory.tracking.click.enabled' => true]);
    $hash = sha1('inject');
    $email = trackedEmail($hash);

    (new InjectMailTracking)->handle(new MessageSending($email));

    expect($email->getHtmlBody())
        ->toContain('mailhistory/track/open/'.$hash)       // open pixel
        ->toContain('mailhistory/track/click/'.$hash)      // rewritten link
        ->not->toContain('href="https://example.com/page"'); // original link replaced
});

it('decodes HTML entities in the tracked URL so signed query strings survive', function () {
    config(['mailhistory.tracking.open.enabled' => false, 'mailhistory.tracking.click.enabled' => true]);
    $hash = sha1('signed');

    // As rendered by Laravel's mail templates, the ampersand in a signed URL is
    // HTML-escaped to &amp; inside the href attribute.
    $signedUrl = 'https://example.com/email/verify/1/abc?expires=123&signature=deadbeef';
    $email = (new Email)->html('<html><body><a href="https://example.com/email/verify/1/abc?expires=123&amp;signature=deadbeef">Verify</a></body></html>');
    $email->getHeaders()->addTextHeader('X-Metadata-hash', $hash);

    (new InjectMailTracking)->handle(new MessageSending($email));

    expect($email->getHtmlBody())->toMatch('/mailhistory\/track\/click/');

    // Extract the encrypted url param and assert it decrypts back to the clean &.
    preg_match('/track\/click\/'.$hash.'\?url=([^"\']+)/', $email->getHtmlBody(), $m);
    $decrypted = Crypt::decryptString(urldecode($m[1]));

    expect($decrypted)
        ->toBe($signedUrl)
        ->not->toContain('&amp;');
});

it('only injects the pixel when click tracking is off', function () {
    config(['mailhistory.tracking.open.enabled' => true, 'mailhistory.tracking.click.enabled' => false]);
    $hash = sha1('open-only');
    $email = trackedEmail($hash);

    (new InjectMailTracking)->handle(new MessageSending($email));

    expect($email->getHtmlBody())
        ->toContain('mailhistory/track/open/'.$hash)
        ->toContain('href="https://example.com/page"'); // link untouched
});

it('does nothing when tracking is disabled', function () {
    config(['mailhistory.tracking.open.enabled' => false, 'mailhistory.tracking.click.enabled' => false]);
    $email = trackedEmail(sha1('off'));
    $original = $email->getHtmlBody();

    (new InjectMailTracking)->handle(new MessageSending($email));

    expect($email->getHtmlBody())->toBe($original);
});

it('does nothing when the message carries no hash header', function () {
    config(['mailhistory.tracking.open.enabled' => true]);
    $email = (new Email)->html('<html><body>No hash</body></html>');
    $original = $email->getHtmlBody();

    (new InjectMailTracking)->handle(new MessageSending($email));

    expect($email->getHtmlBody())->toBe($original);
});
