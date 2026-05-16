<?php

namespace App\Filament\Resources\DnsRecords\Pages;

use App\Filament\Resources\DnsRecords\DnsRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDnsRecords extends ManageRecords
{
    protected static string $resource = DnsRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
