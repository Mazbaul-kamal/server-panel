<?php

namespace App\Services\System;

use InvalidArgumentException;

final class CommandWhitelist
{
    /**
     * @param  array<int, string>  $arguments
     * @return array<int, string>
     */
    public function sudoCommand(string $action, array $arguments = []): array
    {
        return match ($action) {
            'user.add' => $this->userAdd($arguments),
            'fs.mkdir' => $this->mkdir($arguments),
            'fs.chown' => $this->chown($arguments),
            'nginx.site' => $this->nginxSite($arguments),
            'apt.update' => $this->exact('/usr/bin/apt-get', ['update'], $arguments),
            'certbot.renew' => $this->exact('/usr/bin/certbot', ['renew', '--quiet'], $arguments),
            'backup.sites' => $this->exact('/usr/local/sbin/panel-backup-sites', [], $arguments),
            'logs.rotate' => $this->exact('/usr/sbin/logrotate', ['/etc/logrotate.conf'], $arguments),
            'panel.system' => $this->panelSystem($arguments),
            default => throw new InvalidArgumentException('Command is not whitelisted.'),
        };
    }

    /**
     * @param  array<int, string>  $arguments
     * @return array<int, string>
     */
    private function userAdd(array $arguments): array
    {
        if (
            count($arguments) !== 5 ||
            $arguments[0] !== '--system' ||
            $arguments[1] !== '--create-home' ||
            $arguments[2] !== '--shell' ||
            $arguments[3] !== '/usr/sbin/nologin' ||
            ! preg_match('/^[a-z_][a-z0-9_-]{0,31}$/', $arguments[4])
        ) {
            throw new InvalidArgumentException('Invalid useradd invocation.');
        }

        return ['/usr/sbin/useradd', ...$arguments];
    }

    /**
     * @param  array<int, string>  $arguments
     * @return array<int, string>
     */
    private function mkdir(array $arguments): array
    {
        if (
            count($arguments) !== 3 ||
            $arguments[0] !== '-p' ||
            ! preg_match('/^--mode=0?[0-7]{3}$/', $arguments[1]) ||
            ! str_starts_with($arguments[2].'/', rtrim((string) config('server-panel.managed_root'), '/').'/')
        ) {
            throw new InvalidArgumentException('Invalid mkdir invocation.');
        }

        return ['/usr/bin/mkdir', ...$arguments];
    }

    /**
     * @param  array<int, string>  $arguments
     * @return array<int, string>
     */
    private function chown(array $arguments): array
    {
        if (
            count($arguments) !== 3 ||
            $arguments[0] !== '-R' ||
            ! preg_match('/^[a-z_][a-z0-9_-]{0,31}(:[a-z_][a-z0-9_-]{0,31})?$/', $arguments[1]) ||
            ! str_starts_with($arguments[2].'/', rtrim((string) config('server-panel.managed_root'), '/').'/')
        ) {
            throw new InvalidArgumentException('Invalid chown invocation.');
        }

        return ['/usr/bin/chown', ...$arguments];
    }

    /**
     * @param  array<int, string>  $arguments
     * @return array<int, string>
     */
    private function nginxSite(array $arguments): array
    {
        $action = $arguments[0] ?? null;
        $site = $arguments[1] ?? null;
        $helper = (string) config('server-panel.nginx.helper');

        $siteAction = in_array($action, ['write', 'enable', 'disable'], true)
            && count($arguments) === 2
            && is_string($site)
            && preg_match('/^[a-z0-9][a-z0-9.-]{0,252}$/', $site);

        $globalAction = in_array($action, ['test', 'reload'], true) && count($arguments) === 1;

        if (! $siteAction && ! $globalAction) {
            throw new InvalidArgumentException('Invalid nginx helper invocation.');
        }

        return [$helper, ...$arguments];
    }

    /**
     * @param  array<int, string>  $arguments
     * @return array<int, string>
     */
    private function panelSystem(array $arguments): array
    {
        $command = $arguments[0] ?? null;

        $valid = match ($command) {
            'install-module' => count($arguments) === 2
                && preg_match('/^[a-z0-9][a-z0-9-]{0,63}$/', $arguments[1])
                && array_key_exists($arguments[1], (array) config('hosting-modules.modules', [])),
            'service' => count($arguments) === 3
                && in_array($arguments[1], ['start', 'stop', 'restart', 'reload', 'enable', 'disable', 'status'], true)
                && preg_match('/^[a-zA-Z0-9@_.-]{1,96}$/', $arguments[2])
                && (
                    array_key_exists($arguments[2], (array) config('hosting-modules.service_units', []))
                    || $arguments[2] === 'certbot.timer'
                    || preg_match('/^php[0-9]+(\.[0-9]+)?-fpm$/', $arguments[2])
                    || $arguments[2] === 'php-fpm'
                ),
            'firewall' => count($arguments) === 5
                && in_array($arguments[1], ['allow', 'deny', 'delete-allow', 'delete-deny'], true)
                && preg_match('/^[0-9]{1,5}$/', $arguments[2])
                && (int) $arguments[2] >= 1
                && (int) $arguments[2] <= 65535
                && in_array($arguments[3], ['tcp', 'udp'], true)
                && preg_match('/^(any|[0-9a-fA-F:.\/]{3,64})$/', $arguments[4]),
            'backup-path' => count($arguments) === 4
                && $this->pathInside($arguments[1], (string) config('server-panel.managed_root'))
                && $this->pathInside($arguments[2], '/var/backups/server-panel')
                && preg_match('/^[0-9]{1,4}$/', $arguments[3]),
            'site-upload' => count($arguments) === 4
                && $this->pathInside($arguments[1], (string) config('filesystems.disks.local.root'))
                && $this->pathInsideChild($arguments[2], (string) config('server-panel.managed_root'))
                && preg_match('/^[a-z_][a-z0-9_-]{0,31}(:[a-z_][a-z0-9_-]{0,31})?$/', $arguments[3]),
            'git-deploy' => count($arguments) === 6
                && $this->gitRepository($arguments[1])
                && $this->gitBranch($arguments[2])
                && $this->pathInsideChild($arguments[3], (string) config('server-panel.managed_root'))
                && preg_match('/^[a-z_][a-z0-9_-]{0,31}(:[a-z_][a-z0-9_-]{0,31})?$/', $arguments[4])
                && ($arguments[5] === '-' || $this->pathInside($arguments[5], '/etc/server-panel/deploy-keys')),
            default => false,
        };

        if (! $valid) {
            throw new InvalidArgumentException('Invalid panel system invocation.');
        }

        return ['/usr/local/sbin/panel-system', ...$arguments];
    }

    private function pathInside(string $path, string $root): bool
    {
        $root = '/'.trim(preg_replace('#/+#', '/', $root), '/');
        $path = '/'.ltrim(preg_replace('#/+#', '/', $path), '/');

        return ! str_contains($path, "\0")
            && ! preg_match('#(^|/)\.\.(/|$)#', $path)
            && str_starts_with($path.'/', rtrim($root, '/').'/');
    }

    private function pathInsideChild(string $path, string $root): bool
    {
        $root = '/'.trim(preg_replace('#/+#', '/', $root), '/');
        $path = '/'.ltrim(preg_replace('#/+#', '/', $path), '/');

        return $path !== $root && $this->pathInside($path, $root);
    }

    private function gitRepository(string $repository): bool
    {
        return ! str_contains($repository, '..')
            && (
                (bool) preg_match('#^https://[A-Za-z0-9._:-]+/[A-Za-z0-9._/-]+(\.git)?$#', $repository)
                || (bool) preg_match('#^git@[A-Za-z0-9._-]+:[A-Za-z0-9._/-]+(\.git)?$#', $repository)
                || (bool) preg_match('#^ssh://git@[A-Za-z0-9._-]+/[A-Za-z0-9._/-]+(\.git)?$#', $repository)
            );
    }

    private function gitBranch(string $branch): bool
    {
        return ! str_starts_with($branch, '-')
            && ! str_contains($branch, '..')
            && (bool) preg_match('#^[A-Za-z0-9._/-]{1,128}$#', $branch);
    }

    /**
     * @param  array<int, string>  $expected
     * @param  array<int, string>  $arguments
     * @return array<int, string>
     */
    private function exact(string $binary, array $expected, array $arguments): array
    {
        if ($arguments !== $expected) {
            throw new InvalidArgumentException('Invalid command arguments.');
        }

        return [$binary, ...$arguments];
    }
}
