<?php

namespace App\Models\Hosting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailAccount extends Model
{
    protected $fillable = [
        'mail_domain_id',
        'local_part',
        'quota_mb',
        'status',
        'metadata',
        'last_provisioned_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'last_provisioned_at' => 'datetime',
        ];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(MailDomain::class, 'mail_domain_id');
    }
}
