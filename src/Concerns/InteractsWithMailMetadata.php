<?php

namespace CleaniqueCoders\MailHistory\Concerns;

use Illuminate\Support\Str;

trait InteractsWithMailMetadata
{
    public function configureMetadataHash(): self
    {
        if (empty($this->getMetadataHash())) {
            $this->setMetadataHash();
        }

        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadataHash($value = null): self
    {
        $this->metadata('hash', sha1(
            empty($value) ? Str::orderedUuid() : $value
        ));

        return $this;
    }

    public function getMetadataHash(): string
    {
        return data_get($this->metadata, 'hash', '');
    }
}
