<?php

namespace App\Filament\Resources\BackupPlans\Pages;

use App\Filament\Resources\BackupPlans\BackupPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBackupPlans extends ManageRecords
{
    protected static string $resource = BackupPlanResource::class;

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
