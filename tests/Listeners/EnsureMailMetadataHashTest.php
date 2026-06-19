<?php

use CleaniqueCoders\MailHistory\Listeners\EnsureMailMetadataHash;
use CleaniqueCoders\MailHistory\MailHistory;
use Illuminate\Mail\Events\MessageSending;
use Symfony\Component\Mime\Email;

it('stamps an X-Metadata-hash header on a message that has none', function () {
    $email = (new Email)->html('<p>Hi</p>');

    expect(MailHistory::getHashFromHeader($email->getHeaders()->toArray()))->toBeFalse();

    (new EnsureMailMetadataHash)->handle(new MessageSending($email));

    $hash = MailHistory::getHashFromHeader($email->getHeaders()->toArray());
    expect($hash)->toBeString()->not->toBeEmpty();
});

it('leaves an existing X-Metadata-hash header intact', function () {
    $email = (new Email)->html('<p>Hi</p>');
    $email->getHeaders()->addTextHeader('X-Metadata-hash', sha1('existing'));

    (new EnsureMailMetadataHash)->handle(new MessageSending($email));

    expect(MailHistory::getHashFromHeader($email->getHeaders()->toArray()))->toBe(sha1('existing'));
});
