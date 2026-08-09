<?php

namespace App\Filament\Resources\WorkItems;

use App\Filament\Resources\WorkItems\Pages\CreateWorkItem;
use App\Filament\Resources\WorkItems\Pages\EditWorkItem;
use App\Filament\Resources\WorkItems\Pages\ListWorkItems;
use App\Filament\Resources\WorkItems\Schemas\WorkItemForm;
use App\Filament\Resources\WorkItems\Tables\WorkItemsTable;
use App\Models\WorkItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WorkItemResource extends Resource
{
    protected static ?string $model = WorkItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return WorkItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkItemsTable::configure($table);
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
            'index' => ListWorkItems::route('/'),
            'create' => CreateWorkItem::route('/create'),
            'edit' => EditWorkItem::route('/{record}/edit'),
        ];
    }
}
