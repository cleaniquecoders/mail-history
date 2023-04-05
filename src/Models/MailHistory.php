<?php

namespace CleaniqueCoders\MailHistory\Models;

use CleaniqueCoders\MailHistory\Concerns\InteractsWithHash;
use CleaniqueCoders\MailHistory\Concerns\InteractsWithUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailHistory extends Model
{
    use HasFactory;
    use InteractsWithUuid;
    use InteractsWithHash;

    protected $guarded = [
        'id'
    ];

    protected $casts = [
        'headers' => 'array',
        'content' => 'array',
    ];
}
