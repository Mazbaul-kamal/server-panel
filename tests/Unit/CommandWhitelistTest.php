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
}
