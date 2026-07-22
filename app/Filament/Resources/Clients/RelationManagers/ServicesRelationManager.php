<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use App\Filament\Resources\Services\Schemas\ServiceForm;
use App\Filament\Resources\Services\Tables\ServicesTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use Filament\Tables\Table;

class ServicesRelationManager extends RelationManager
{
    protected static string $relationship = 'services';

    public function form(Schema $schema): Schema
    {
        return ServiceForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return ServicesTable::configure($table)
            ->headerActions([
                CreateAction::make()
                    ->label('New Service'),
            ]);
    }
}
