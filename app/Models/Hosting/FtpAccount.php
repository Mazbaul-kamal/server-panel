<?php

namespace App\Models\Hosting;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FtpAccount extends Model
{
    protected $fillable = [
        'user_id',
        'username',
        'root_path',
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
}
