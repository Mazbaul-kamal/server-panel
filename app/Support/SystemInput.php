<?php

namespace App\Support;

use InvalidArgumentException;

final class SystemInput
{
    public static function linuxName(string $value): string
    {
        if (! preg_match('/^[a-z_][a-z0-9_-]{0,31}$/', $value)) {
            throw new InvalidArgumentException('Invalid Linux name.');
        }

        return $value;
    }

    public static function domain(string $value): string
    {
        $value = strtolower(trim($value));

        if (! preg_match('/^(?=.{1,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $value)) {
            throw new InvalidArgumentException('Invalid domain name.');
        }

        return $value;
    }

    /**
     * @param  array<int, string>  $aliases
     * @return array<int, string>
     */
    public static function domains(array $aliases): array
    {
        return array_values(array_unique(array_map(fn (string $alias) => self::domain($alias), $aliases)));
    }

    public static function siteName(string $value): string
    {
        $value = strtolower(trim($value));

        if (! preg_match('/^[a-z0-9][a-z0-9.-]{0,252}$/', $value)) {
            throw new InvalidArgumentException('Invalid site name.');
        }

        return $value;
    }

    public static function managedPath(string $path): string
    {
        $root = rtrim((string) config('server-panel.managed_root'), '/');
        $path = '/'.ltrim(preg_replace('#/+#', '/', $path), '/');

        if (str_contains($path, "\0") || preg_match('#(^|/)\.\.(/|$)#', $path)) {
            throw new InvalidArgumentException('Invalid path.');
        }

        if (! str_starts_with($path.'/', $root.'/')) {
            throw new InvalidArgumentException("Path must stay inside {$root}.");
        }

        return $path;
    }

    public static function unixOwner(string $owner): string
    {
        if (! preg_match('/^[a-z_][a-z0-9_-]{0,31}(:[a-z_][a-z0-9_-]{0,31})?$/', $owner)) {
            throw new InvalidArgumentException('Invalid owner.');
        }

        return $owner;
    }

    public static function fileMode(int $mode): string
    {
        $mode = decoct($mode);

        if (! preg_match('/^0?[0-7]{3}$/', $mode)) {
            throw new InvalidArgumentException('Invalid mode.');
        }

        return $mode;
    }

    public static function databaseIdentifier(string $value): string
    {
        if (! preg_match('/^[a-zA-Z0-9_]{1,64}$/', $value)) {
            throw new InvalidArgumentException('Invalid database identifier.');
        }

        return $value;
    }

    public static function databaseHost(string $value): string
    {
        if (! preg_match('/^[a-zA-Z0-9_.:%-]{1,255}$/', $value)) {
            throw new InvalidArgumentException('Invalid database host.');
        }

        return $value;
    }
}
