<?php

namespace App\Services\System;

final class ServerMetrics
{
    /**
     * @return array{load: string, memory: string, disk: string}
     */
    public function snapshot(): array
    {
        return [
            'load' => $this->loadAverage(),
            'memory' => $this->memoryUsage(),
            'disk' => $this->diskUsage('/'),
        ];
    }

    private function loadAverage(): string
    {
        $load = sys_getloadavg();

        return $load === false ? 'n/a' : number_format($load[0], 2);
    }

    private function memoryUsage(): string
    {
        $meminfo = @file('/proc/meminfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($meminfo === false) {
            return 'n/a';
        }

        $values = [];

        foreach ($meminfo as $line) {
            [$key, $value] = explode(':', $line, 2);
            $values[$key] = (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
        }

        if (! isset($values['MemTotal'], $values['MemAvailable'])) {
            return 'n/a';
        }

        $used = $values['MemTotal'] - $values['MemAvailable'];

        return number_format(($used / $values['MemTotal']) * 100, 1).'%';
    }

    private function diskUsage(string $path): string
    {
        $total = @disk_total_space($path);
        $free = @disk_free_space($path);

        if (! $total || $free === false) {
            return 'n/a';
        }

        return number_format((($total - $free) / $total) * 100, 1).'%';
    }
}
