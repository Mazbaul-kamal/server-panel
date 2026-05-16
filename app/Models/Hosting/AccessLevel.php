<?php

namespace App\Models\Hosting;

use Illuminate\Database\Eloquent\Model;

class AccessLevel extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'permissions',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }
}
