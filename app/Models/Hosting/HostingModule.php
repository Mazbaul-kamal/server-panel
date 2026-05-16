<?php

namespace App\Models\Hosting;

use Illuminate\Database\Eloquent\Model;

class HostingModule extends Model
{
    protected $fillable = [
        'key',
        'name',
        'category',
        'status',
        'enabled',
        'description',
        'packages',
        'services',
        'ports',
        'metadata',
        'installed_at',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'packages' => 'array',
            'services' => 'array',
            'ports' => 'array',
            'metadata' => 'array',
            'installed_at' => 'datetime',
        ];
    }
}
