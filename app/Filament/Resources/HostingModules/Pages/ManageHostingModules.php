<?php

namespace App\Filament\Resources\HostingModules\Pages;

use App\Filament\Resources\HostingModules\HostingModuleResource;
use App\Services\Hosting\HostingCatalog;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

class ManageHostingModules extends ManageRecords
{
    protected static string $resource = HostingModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncCatalog')
                ->label('Sync catalog')
                ->icon(Heroicon::OutlinedArrowPath)
                ->action(function (HostingCatalog $catalog): void {
                    $catalog->sync();

                    Notification::make()->title('Catalog synced')->success()->send();
                }),
        ];
    }
}
