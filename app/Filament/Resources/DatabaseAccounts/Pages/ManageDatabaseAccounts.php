<?php

namespace App\Filament\Resources\DatabaseAccounts\Pages;

use App\Filament\Resources\DatabaseAccounts\DatabaseAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDatabaseAccounts extends ManageRecords
{
    protected static string $resource = DatabaseAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateDataUsing(function (array $data): array {
                    $data['user_id'] = auth()->id();

                    return $data;
                }),
        ];
    }
}
