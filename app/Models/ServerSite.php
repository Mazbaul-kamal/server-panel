<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerSite extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'domain',
        'aliases',
        'document_root',
        'system_user',
        'php_fpm_socket',
        'ssl_enabled',
        'git_repository',
        'git_branch',
        'git_deploy_key_path',
        'status',
        'last_deployed_at',
        'last_git_deployed_at',
        'last_file_uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'aliases' => 'array',
            'ssl_enabled' => 'boolean',
            'last_deployed_at' => 'datetime',
            'last_git_deployed_at' => 'datetime',
            'last_file_uploaded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
