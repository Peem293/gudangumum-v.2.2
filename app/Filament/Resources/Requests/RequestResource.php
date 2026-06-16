<?php

namespace App\Filament\Resources\Requests;

use App\Filament\Resources\Requests\Pages\CreateRequest;
use App\Filament\Resources\Requests\Pages\EditRequest;
use App\Filament\Resources\Requests\Pages\ListRequests;
use App\Filament\Resources\Requests\Pages\ViewRequest;
use App\Filament\Resources\Requests\Schemas\RequestForm;
use App\Filament\Resources\Requests\Tables\RequestsTable;
use App\Models\Request;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RequestResource extends Resource
{
    protected static ?string $model = Request::class;

    protected static ?string $slug = 'requests';
    protected static ?string $navigationLabel = 'Unit Requests';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $recordTitleAttribute = 'request_number';

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery();

        if (!$user) {
            return $query;
        }

        // Administrator, Direktur, dan Admin Gudang: akses global
        if ($user->hasRole(['administrator', 'direktur', 'admin_gudang'])) {
            return $query;
        }

        // Manager Keuangan: lihat SEMUA request dari semua unit/departemen (read-only)
        if ($user->isFinanceManager()) {
            return $query;
        }

        // Manager Penunjang Umum: lihat request dalam departemennya saja
        if ($user->isPurchasingManager()) {
            return $query->where('department_id', $user->department_id);
        }

        // Manager biasa: lihat request dalam departemennya saja
        if ($user->hasRole('manager')) {
            return $query->where('department_id', $user->department_id);
        }

        // Staf Unit: hanya request dari unitnya sendiri
        if ($user->hasRole('staf_unit')) {
            return $query->where('unit_id', $user->unit_id);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return RequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRequests::route('/'),
            'create' => CreateRequest::route('/create'),
            'view' => ViewRequest::route('/{record}'),
            'edit' => EditRequest::route('/{record}/edit'),
        ];
    }
}
