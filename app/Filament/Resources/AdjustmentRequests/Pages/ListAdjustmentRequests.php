<?php

namespace App\Filament\Resources\AdjustmentRequests\Pages;

use App\Filament\Resources\AdjustmentRequests\AdjustmentRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAdjustmentRequests extends ListRecords
{
    protected static string $resource = AdjustmentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
