<?php

namespace CleaniqueCoders\MailHistory\Models;

use CleaniqueCoders\Traitify\Concerns\InteractsWithUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailHistoryEvent extends Model
{
    use InteractsWithUuid;

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function mailHistory(): BelongsTo
    {
        return $this->belongsTo(config('mailhistory.model', MailHistory::class));
    }
}
