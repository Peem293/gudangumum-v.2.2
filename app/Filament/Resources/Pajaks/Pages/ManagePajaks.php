<?php

namespace App\Filament\Resources\Pajaks\Pages;

use App\Filament\Resources\Pajaks\PajakResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePajaks extends ManageRecords
{
    protected static string $resource = PajakResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
