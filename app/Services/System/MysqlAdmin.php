<?php

namespace App\Services\System;

use App\Support\SystemInput;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class MysqlAdmin
{
    private const ALLOWED_PRIVILEGES = [
        'SELECT',
        'INSERT',
        'UPDATE',
        'DELETE',
        'CREATE',
        'DROP',
        'INDEX',
        'ALTER',
        'REFERENCES',
        'CREATE TEMPORARY TABLES',
        'LOCK TABLES',
    ];

    /**
     * @param  array<int, string>  $privileges
     */
    public function createDatabaseUser(string $database, string $username, string $password, array $privileges, string $host = 'localhost'): void
    {
        $connection = DB::connection('server_mysql');
        $db = $this->identifier(SystemInput::databaseIdentifier($database));
        $user = $this->literal(SystemInput::databaseIdentifier($username));
        $hostLiteral = $this->literal(SystemInput::databaseHost($host));
        $pass = $this->literal($password);
        $privilegeSql = $this->privileges($privileges);

        $connection->statement("CREATE DATABASE IF NOT EXISTS {$db} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $connection->statement("CREATE USER IF NOT EXISTS {$user}@{$hostLiteral} IDENTIFIED BY {$pass}");
        $connection->statement("GRANT {$privilegeSql} ON {$db}.* TO {$user}@{$hostLiteral}");
    }

    /**
     * @param  array<int, string>  $privileges
     */
    public function updatePrivileges(string $database, string $username, array $privileges, string $host = 'localhost'): void
    {
        $connection = DB::connection('server_mysql');
        $db = $this->identifier(SystemInput::databaseIdentifier($database));
        $user = $this->literal(SystemInput::databaseIdentifier($username));
        $hostLiteral = $this->literal(SystemInput::databaseHost($host));
        $privilegeSql = $this->privileges($privileges);

        $connection->statement("REVOKE ALL PRIVILEGES, GRANT OPTION FROM {$user}@{$hostLiteral}");
        $connection->statement("GRANT {$privilegeSql} ON {$db}.* TO {$user}@{$hostLiteral}");
    }

    private function identifier(string $value): string
    {
        return '`'.str_replace('`', '``', $value).'`';
    }

    private function literal(string $value): string
    {
        return DB::connection('server_mysql')->getPdo()->quote($value);
    }

    /**
     * @param  array<int, string>  $privileges
     */
    private function privileges(array $privileges): string
    {
        $privileges = array_values(array_unique(array_map(fn (string $privilege) => strtoupper($privilege), $privileges)));

        if ($privileges === [] || array_diff($privileges, self::ALLOWED_PRIVILEGES)) {
            throw new InvalidArgumentException('Invalid privilege set.');
        }

        return implode(', ', $privileges);
    }
}
