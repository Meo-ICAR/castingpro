<?php

namespace App\Filament\Resources\Actors;

use App\Filament\Resources\Actors\Pages\CreateActor;
use App\Filament\Resources\Actors\Pages\EditActor;
use App\Filament\Resources\Actors\Pages\ListActors;
use App\Filament\Resources\Actors\Schemas\ActorForm;
use App\Filament\Resources\Actors\Tables\ActorsTable;
use App\Models\Actor;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use BackedEnum;
use UnitEnum;

class ActorResource extends Resource
{
    protected static ?string $model = Actor::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Attori';

    protected static ?string $modelLabel = 'Attore';

    protected static ?string $pluralModelLabel = 'Attori';

    protected static UnitEnum|string|null $navigationGroup = 'Database';

    public static function form(Schema $schema): Schema
    {
        return ActorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActorsTable::configure($table);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['media']);
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
            'index' => ListActors::route('/'),
            'create' => CreateActor::route('/create'),
            'edit' => EditActor::route('/{record}/edit'),
        ];
    }
}
