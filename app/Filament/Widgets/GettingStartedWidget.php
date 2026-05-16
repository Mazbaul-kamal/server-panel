<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\HostingModules\HostingModuleResource;
use App\Filament\Resources\ServerSites\ServerSiteResource;
use App\Filament\Resources\Tasks\TaskResource;
use Filament\Widgets\Widget;

class GettingStartedWidget extends Widget
{
    protected static ?int $sort = -20;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.getting-started';

    protected function getViewData(): array
    {
        return [
            'steps' => [
                [
                    'number' => '01',
                    'title' => 'Create website',
                    'detail' => 'Add domain and deploy Nginx.',
                    'url' => ServerSiteResource::getUrl('index'),
                    'button' => 'Open websites',
                ],
                [
                    'number' => '02',
                    'title' => 'Upload files',
                    'detail' => 'Use Upload ZIP on a website row.',
                    'url' => ServerSiteResource::getUrl('index'),
                    'button' => 'Upload ZIP',
                ],
                [
                    'number' => '03',
                    'title' => 'Connect Git',
                    'detail' => 'Set repository and run Deploy Git.',
                    'url' => ServerSiteResource::getUrl('index'),
                    'button' => 'Deploy Git',
                ],
                [
                    'number' => '04',
                    'title' => 'Track progress',
                    'detail' => 'Watch task logs and command output.',
                    'url' => TaskResource::getUrl('index'),
                    'button' => 'View tasks',
                ],
            ],
            'catalogUrl' => HostingModuleResource::getUrl('index'),
        ];
    }
}
