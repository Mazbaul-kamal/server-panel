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
