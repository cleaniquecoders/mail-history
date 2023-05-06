<?php

namespace CleaniqueCoders\MailHistory\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait InteractsWithHash
{
    /**
     * Get Hash Column Name.
     */
    public function getHashColumnName(): string
    {
        return isset($this->hash_column) ? $this->hash_column : 'hash';
    }

    /**
     * Scope a query to only include popular users.
     */
    public function scopeHash(Builder $query, $value): Builder
    {
        return $query->where($this->getHashColumnName(), $value);
    }
}
