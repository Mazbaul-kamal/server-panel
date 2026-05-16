<?php

namespace App\Filament\Resources\DnsZones\Pages;

use App\Filament\Resources\DnsZones\DnsZoneResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDnsZones extends ManageRecords
{
    protected static string $resource = DnsZoneResource::class;

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
