<?php

namespace Tests\Unit;

use App\Services\System\CommandWhitelist;
use InvalidArgumentException;
use Tests\TestCase;

class CommandWhitelistTest extends TestCase
{
    public function test_apt_update_is_exactly_whitelisted(): void
    {
        $command = app(CommandWhitelist::class)->sudoCommand('apt.update', ['update']);

        $this->assertSame(['/usr/bin/apt-get', 'update'], $command);
    }

    public function test_apt_install_cannot_be_smuggled_through_update_action(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(CommandWhitelist::class)->sudoCommand('apt.update', ['install', 'nginx']);
    }

    public function test_nginx_helper_rejects_injected_site_names(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(CommandWhitelist::class)->sudoCommand('nginx.site', ['enable', 'site;rm']);
    }

    public function test_panel_system_allows_known_module_install_shape(): void
    {
        $command = app(CommandWhitelist::class)->sudoCommand('panel.system', ['install-module', 'dns']);

        $this->assertSame(['/usr/local/sbin/panel-system', 'install-module', 'dns'], $command);
    }

    public function test_panel_system_rejects_injected_service_units(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(CommandWhitelist::class)->sudoCommand('panel.system', ['service', 'restart', 'nginx;rm']);
    }

    public function test_panel_system_rejects_unknown_module_installs(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(CommandWhitelist::class)->sudoCommand('panel.system', ['install-module', 'unknown-module']);
    }

    public function test_panel_system_rejects_firewall_ports_outside_valid_range(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(CommandWhitelist::class)->sudoCommand('panel.system', ['firewall', 'allow', '70000', 'tcp', 'any']);
    }

    public function test_panel_system_rejects_backups_outside_managed_root(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(CommandWhitelist::class)->sudoCommand('panel.system', ['backup-path', '/etc', '/var/backups/server-panel/etc', '14']);
    }

    public function test_panel_system_allows_site_upload_from_private_storage(): void
    {
        $source = storage_path('app/private/site-uploads/site.zip');
        $destination = rtrim((string) config('server-panel.managed_root'), '/').'/example.com/public';

        $command = app(CommandWhitelist::class)->sudoCommand('panel.system', ['site-upload', $source, $destination, 'www-data']);

        $this->assertSame(['/usr/local/sbin/panel-system', 'site-upload', $source, $destination, 'www-data'], $command);
    }

    public function test_panel_system_rejects_site_upload_to_managed_root_itself(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(CommandWhitelist::class)->sudoCommand('panel.system', [
            'site-upload',
            storage_path('app/private/site-uploads/site.zip'),
            rtrim((string) config('server-panel.managed_root'), '/'),
            'www-data',
        ]);
    }

    public function test_panel_system_allows_https_git_deploy(): void
    {
        $destination = rtrim((string) config('server-panel.managed_root'), '/').'/example.com/public';

        $command = app(CommandWhitelist::class)->sudoCommand('panel.system', [
            'git-deploy',
            'https://github.com/example/site.git',
            'main',
            $destination,
            'www-data',
            '-',
        ]);

        $this->assertSame([
            '/usr/local/sbin/panel-system',
            'git-deploy',
            'https://github.com/example/site.git',
            'main',
            $destination,
            'www-data',
            '-',
        ], $command);
    }

    public function test_panel_system_rejects_git_deploy_with_unsafe_branch(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(CommandWhitelist::class)->sudoCommand('panel.system', [
            'git-deploy',
            'https://github.com/example/site.git',
            '../main',
            rtrim((string) config('server-panel.managed_root'), '/').'/example.com/public',
            'www-data',
            '-',
        ]);
    }
}
