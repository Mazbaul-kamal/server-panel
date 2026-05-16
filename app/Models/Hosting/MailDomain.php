<?php

namespace App\Models\Hosting;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MailDomain extends Model
{
    protected $fillable = [
        'user_id',
        'domain',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(MailAccount::class);
    }
}
