<?php

namespace App\Services\Hosting;

use App\Models\Hosting\HostingModule;
use App\Models\Hosting\SystemService;

final class HostingCatalog
{
    public function sync(): void
    {
        foreach (config('hosting-modules.modules', []) as $key => $module) {
            HostingModule::updateOrCreate(
                ['key' => $key],
                [
                    'name' => $module['name'],
                    'category' => $module['category'],
                    'description' => $module['description'],
                    'packages' => $module['packages'],
                    'services' => $module['services'],
                    'ports' => $module['ports'],
                ],
            );
        }

        foreach (config('hosting-modules.service_units', []) as $unit => $service) {
            SystemService::updateOrCreate(
                ['unit' => $unit],
                [
                    'key' => str($unit)->replace(['.', '@'], '-')->slug()->toString(),
                    'display_name' => $service['name'],
                    'package' => $service['package'],
                ],
            );
        }
    }
}
