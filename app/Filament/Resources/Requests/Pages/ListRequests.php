<?php

namespace App\Filament\Resources\Requests\Pages;

use App\Filament\Resources\Requests\RequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListRequests extends ListRecords
{
    protected static string $resource = RequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            Tab::make('pending')
                ->label('Pending')
                ->icon('heroicon-s-clock')
                ->query(fn (Builder $query) => $query->where('status', 'pending')),
            Tab::make('approved')
                ->label('Approved')
                ->icon('heroicon-s-check')
                ->query(fn (Builder $query) => $query->where('status', 'approved')),
            Tab::make('completed')
                ->label('Completed')
                ->icon('heroicon-s-flag')
                ->query(fn (Builder $query) => $query->where('status', 'completed')),
        ];
    }

}
