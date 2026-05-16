<?php

namespace App\Models\Hosting;

use Illuminate\Database\Eloquent\Model;

class FirewallRule extends Model
{
    protected $fillable = [
        'action',
        'protocol',
        'port',
        'source',
        'status',
        'description',
        'last_applied_at',
    ];

    protected function casts(): array
    {
        return [
            'last_applied_at' => 'datetime',
        ];
    }
}
