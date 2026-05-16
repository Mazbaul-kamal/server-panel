<?php

namespace App\Filament\Resources\MailDomains\Pages;

use App\Filament\Resources\MailDomains\MailDomainResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMailDomains extends ManageRecords
{
    protected static string $resource = MailDomainResource::class;

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
