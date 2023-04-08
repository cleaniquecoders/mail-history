<?php

namespace CleaniqueCoders\MailHistory\Models;

use CleaniqueCoders\MailHistory\Concerns\InteractsWithHash;
use CleaniqueCoders\MailHistory\Concerns\InteractsWithUuid;
use CleaniqueCoders\MailHistory\Contracts\HashContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailHistory extends Model implements HashContract
{
    use HasFactory;
    use InteractsWithUuid;
    use InteractsWithHash;

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'headers' => 'array',
        'content' => 'array',
    ];
}
