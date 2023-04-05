<?php

namespace CleaniqueCoders\MailHistory\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait InteractsWithHash
{
    public static function bootInteractsWithHash()
    {
        static::creating(function (Model $model) {
            if (Schema::hasColumn($model->getTable(), $model->getHashColumnName()) && is_null($model->{$model->getHashColumnName()})) {
                $model->{$model->getHashColumnName()} = self::generateHashValue();
            }
        });
    }

    /**
     * Generate Hash Value
     */
    public static function generateHashValue(array $value = []): string
    {
        return md5(
            count($value) == 0 ? Str::random(32) : implode('.', $value)
        );
    }

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
