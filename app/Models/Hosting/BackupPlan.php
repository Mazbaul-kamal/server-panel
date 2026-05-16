<?php

namespace App\Models\Hosting;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BackupPlan extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'source_path',
        'destination_path',
        'schedule',
        'retention_days',
        'enabled',
        'status',
        'metadata',
        'last_run_at',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'metadata' => 'array',
            'last_run_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(BackupRun::class);
    }
}
