<?php

namespace App\Models\Hosting;

use Illuminate\Database\Eloquent\Model;

class SystemService extends Model
{
    protected $fillable = [
        'key',
        'display_name',
        'unit',
        'package',
        'status',
        'enabled',
        'metadata',
        'last_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'metadata' => 'array',
            'last_checked_at' => 'datetime',
        ];
    }
}
