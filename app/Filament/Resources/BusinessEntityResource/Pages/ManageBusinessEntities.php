<?php

namespace App\Filament\Resources\BusinessEntityResource\Pages;

use App\Filament\Resources\BusinessEntityResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageBusinessEntities extends ManageRecords
{
    protected static string $resource = BusinessEntityResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
