<?php

namespace App\Filament\Resources\DockerResources\Pages;

use App\Filament\Resources\DockerResources\DockerResourceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDockerResources extends ManageRecords
{
    protected static string $resource = DockerResourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
