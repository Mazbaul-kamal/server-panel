<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseAccount extends Model
{
    protected $fillable = [
        'user_id',
        'database',
        'username',
        'host',
        'privileges',
        'status',
        'last_provisioned_at',
    ];

    protected function casts(): array
    {
        return [
            'privileges' => 'array',
            'last_provisioned_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
