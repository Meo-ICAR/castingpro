<?php

namespace App\Filament\Resources\Projects;

use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Filament\Resources\Projects\Schemas\ProjectForm;
use App\Filament\Resources\Projects\Schemas\ProjectInfolist;
use App\Filament\Resources\Projects\Tables\ProjectsTable;
use App\Filament\Resources\Projects\Tables\ProjectsTableView;
use App\Models\Project;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use BackedEnum;
use UnitEnum;
use \Illuminate\Database\Eloquent\Builder;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFilm;

    protected static ?string $navigationLabel = 'Produzione';

    protected static ?string $modelLabel = 'Produzione';

    protected static ?string $pluralModelLabel = 'Produzioni';

    protected static UnitEnum|string|null $navigationGroup = 'Richieste';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return ProjectForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProjectInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        // Default table view
        if (request()->has('view') && request()->get('view') === 'custom') {
            return ProjectsTableView::configure($table);
        }
        return ProjectsTable::configure($table);
    }

    public static function getTable2(Table $table): Table
    {
        // Alternative table view
        return ProjectsTableView::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RolesRelationManager::class,
            RelationManagers\ProjectSeriviceRelationManager::class,
            RelationManagers\ProjectLocationRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['owner', 'roles', 'media']);

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'view' => ViewProject::route('/{record}'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }

    public static function getNavigationItems(): array
    {
        $items = parent::getNavigationItems();

        return $items;
    }
}
