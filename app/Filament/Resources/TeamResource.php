<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeamResource\Pages;
use App\Models\Team;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static \UnitEnum|string|null $navigationGroup = 'User & Access Management';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Team Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('team_lead_id')
                            ->label('Team Leader')
                            ->relationship('leader', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Team Members')
                    ->schema([
                        Forms\Components\Select::make('members')
                            ->relationship('members', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('leader.name')
                    ->label('Team Lead')
                    ->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('members_count')
                    ->label('Members')
                    ->counts('members')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'edit' => Pages\EditTeam::route('/{record}/edit'),
        ];
    }
}
