<?php

namespace App\Models\Hosting;

use Illuminate\Database\Eloquent\Model;

class PhpVersion extends Model
{
    protected $fillable = [
        'version',
        'fpm_unit',
        'fpm_socket',
        'status',
        'extensions',
        'installed_at',
    ];

    protected function casts(): array
    {
        return [
            'extensions' => 'array',
            'installed_at' => 'datetime',
        ];
    }
}
