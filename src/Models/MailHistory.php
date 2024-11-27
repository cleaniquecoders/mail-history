<?php

namespace CleaniqueCoders\MailHistory\Models;

use CleaniqueCoders\MailHistory\Concerns\InteractsWithHash;
use CleaniqueCoders\Traitify\Concerns\InteractsWithUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailHistory extends Model
{
    use HasFactory;
    use InteractsWithHash;
    use InteractsWithUuid;

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'headers' => 'array',
        'content' => 'array',
        'meta' => 'array',
    ];
}
