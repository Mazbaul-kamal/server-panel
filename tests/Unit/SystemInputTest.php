<?php

namespace Tests\Unit;

use App\Support\SystemInput;
use InvalidArgumentException;
use Tests\TestCase;

class SystemInputTest extends TestCase
{
    public function test_managed_paths_must_stay_inside_configured_root(): void
    {
        config()->set('server-panel.managed_root', '/var/www/vhosts');

        $this->assertSame('/var/www/vhosts/example.com', SystemInput::managedPath('/var/www/vhosts/example.com'));

        $this->expectException(InvalidArgumentException::class);

        SystemInput::managedPath('/etc/passwd');
    }

    public function test_domains_are_strictly_validated(): void
    {
        $this->assertSame('example.com', SystemInput::domain('Example.COM'));

        $this->expectException(InvalidArgumentException::class);

        SystemInput::domain('example.com; rm -rf /');
    }
}
