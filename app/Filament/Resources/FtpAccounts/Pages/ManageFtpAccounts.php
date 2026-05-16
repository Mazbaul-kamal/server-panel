<?php

namespace App\Filament\Resources\FtpAccounts\Pages;

use App\Filament\Resources\FtpAccounts\FtpAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFtpAccounts extends ManageRecords
{
    protected static string $resource = FtpAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->mutateDataUsing(function (array $data): array {
                $data['user_id'] = auth()->id();

                return $data;
            }),
        ];
    }
}
