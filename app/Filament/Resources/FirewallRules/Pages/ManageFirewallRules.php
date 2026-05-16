<?php

namespace App\Filament\Resources\FirewallRules\Pages;

use App\Filament\Resources\FirewallRules\FirewallRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFirewallRules extends ManageRecords
{
    protected static string $resource = FirewallRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
