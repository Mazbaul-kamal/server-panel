<?php

return [
    'managed_root' => env('PANEL_MANAGED_ROOT', '/var/www/vhosts'),

    'nginx' => [
        'sites_available' => env('PANEL_NGINX_SITES_AVAILABLE', '/etc/nginx/sites-available'),
        'sites_enabled' => env('PANEL_NGINX_SITES_ENABLED', '/etc/nginx/sites-enabled'),
        'helper' => env('PANEL_NGINX_HELPER', '/usr/local/sbin/panel-nginx-site'),
        'php_fpm_socket' => env('PANEL_PHP_FPM_SOCKET', 'unix:/run/php/php8.3-fpm.sock'),
    ],

    'process' => [
        'sudo' => env('PANEL_SUDO_BINARY', '/usr/bin/sudo'),
        'timeout' => env('PANEL_PROCESS_TIMEOUT', 3600),
        'idle_timeout' => env('PANEL_PROCESS_IDLE_TIMEOUT', 300),
        'path' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
    ],
];
