<?php

namespace App\Services\System;

use App\Support\SystemInput;
use Closure;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Process;

final class ServerAction
{
    public function __construct(private readonly CommandWhitelist $whitelist) {}

    public function addUser(string $username): ProcessResult
    {
        return $this->sudo('user.add', [
            '--system',
            '--create-home',
            '--shell',
            '/usr/sbin/nologin',
            SystemInput::linuxName($username),
        ]);
    }

    public function mkdir(string $path, int $mode = 750): ProcessResult
    {
        return $this->sudo('fs.mkdir', [
            '-p',
            '--mode='.SystemInput::fileMode($mode),
            SystemInput::managedPath($path),
        ]);
    }

    public function chown(string $path, string $owner): ProcessResult
    {
        return $this->sudo('fs.chown', [
            '-R',
            SystemInput::unixOwner($owner),
            SystemInput::managedPath($path),
        ]);
    }

    public function writeNginxSite(string $site, string $content): ProcessResult
    {
        return $this->sudo('nginx.site', ['write', SystemInput::siteName($site)], input: $content);
    }

    public function enableNginxSite(string $site): ProcessResult
    {
        return $this->sudo('nginx.site', ['enable', SystemInput::siteName($site)]);
    }

    public function disableNginxSite(string $site): ProcessResult
    {
        return $this->sudo('nginx.site', ['disable', SystemInput::siteName($site)]);
    }

    public function testNginx(): ProcessResult
    {
        return $this->sudo('nginx.site', ['test']);
    }

    public function reloadNginx(): ProcessResult
    {
        return $this->sudo('nginx.site', ['reload']);
    }

    public function aptUpdate(?Closure $output = null): ProcessResult
    {
        return $this->sudo('apt.update', ['update'], output: $output, timeout: 7200);
    }

    public function renewCertificates(?Closure $output = null): ProcessResult
    {
        return $this->sudo('certbot.renew', ['renew', '--quiet'], output: $output, timeout: 7200);
    }

    public function backupSites(?Closure $output = null): ProcessResult
    {
        return $this->sudo('backup.sites', [], output: $output, timeout: 14400);
    }

    public function rotateLogs(?Closure $output = null): ProcessResult
    {
        return $this->sudo('logs.rotate', ['/etc/logrotate.conf'], output: $output, timeout: 1800);
    }

    public function runWhitelisted(string $action, array $arguments = [], ?Closure $output = null): ProcessResult
    {
        return $this->sudo($action, $arguments, output: $output, timeout: (int) config('server-panel.process.timeout'));
    }

    public function composerInstall(string $releasePath, ?Closure $output = null): ProcessResult
    {
        return Process::path(SystemInput::managedPath($releasePath))
            ->timeout(3600)
            ->idleTimeout((int) config('server-panel.process.idle_timeout'))
            ->env($this->environment([
                'COMPOSER_HOME' => storage_path('app/composer'),
            ]))
            ->run(['/usr/bin/composer', 'install', '--no-dev', '--no-interaction', '--prefer-dist'], $output)
            ->throw();
    }

    private function sudo(
        string $action,
        array $arguments = [],
        int $timeout = 60,
        ?string $input = null,
        ?Closure $output = null,
    ): ProcessResult {
        $command = [
            (string) config('server-panel.process.sudo'),
            '-n',
            ...$this->whitelist->sudoCommand($action, $arguments),
        ];

        return Process::timeout($timeout)
            ->idleTimeout((int) config('server-panel.process.idle_timeout'))
            ->env($this->environment())
            ->when($input !== null, fn ($process) => $process->input($input))
            ->run($command, $output)
            ->throw();
    }

    private function environment(array $extra = []): array
    {
        return [
            'PATH' => config('server-panel.process.path'),
            'LC_ALL' => 'C',
            'HOME' => '/var/www',
            ...$extra,
        ];
    }
}
