<?php

namespace App\Services\System;

use App\Models\ServerSite;
use App\Support\SystemInput;
use Throwable;

final class NginxSiteService
{
    public function __construct(private readonly ServerAction $server) {}

    public function deploy(ServerSite $site): void
    {
        $siteName = SystemInput::siteName($site->name);
        $domain = SystemInput::domain($site->domain);
        $aliases = SystemInput::domains($site->aliases ?? []);
        $documentRoot = SystemInput::managedPath($site->document_root);

        $content = view('system.nginx-site', [
            'siteName' => $siteName,
            'domain' => $domain,
            'aliases' => $aliases,
            'documentRoot' => $documentRoot,
            'phpFpmSocket' => $site->php_fpm_socket ?: config('server-panel.nginx.php_fpm_socket'),
        ])->render();

        try {
            $this->server->mkdir($documentRoot.'/public', 750);
            $this->server->writeNginxSite($siteName, $content);
            $this->server->enableNginxSite($siteName);
            $this->server->testNginx();
            $this->server->reloadNginx();

            $site->forceFill([
                'status' => 'active',
                'last_deployed_at' => now(),
            ])->save();
        } catch (Throwable $e) {
            $this->server->disableNginxSite($siteName);
            $site->forceFill(['status' => 'failed'])->save();

            throw $e;
        }
    }
}
