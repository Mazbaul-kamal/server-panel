<?php

namespace App\Filament\Resources\AccessLevels\Pages;

use App\Filament\Resources\AccessLevels\AccessLevelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAccessLevels extends ManageRecords
{
    protected static string $resource = AccessLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
