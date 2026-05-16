<?php

namespace App\Filament\Resources\PhpVersions\Pages;

use App\Filament\Resources\PhpVersions\PhpVersionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePhpVersions extends ManageRecords
{
    protected static string $resource = PhpVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
