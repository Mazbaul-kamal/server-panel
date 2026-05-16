<?php

namespace App\Models\Hosting;

use Illuminate\Database\Eloquent\Model;

class DockerResource extends Model
{
    protected $fillable = [
        'name',
        'type',
        'image',
        'status',
        'ports',
        'metadata',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'ports' => 'array',
            'metadata' => 'array',
            'last_seen_at' => 'datetime',
        ];
    }
}
