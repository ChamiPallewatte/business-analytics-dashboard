<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Models\Report;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static \UnitEnum|string|null $navigationGroup = 'Analytics & Insights';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Report Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('type')
                            ->label('Report Type')
                            ->options([
                                'financial' => 'Financial Performance Summary',
                                'campaign' => 'Digital Marketing Campaign ROI',
                                'inventory' => 'Stock & Inventory Analytics',
                                'reservation' => 'Orders & Reservations Report',
                                'general' => 'Executive KPI Overview',
                            ])
                            ->required(),
                        Forms\Components\Select::make('format')
                            ->options([
                                'pdf' => 'PDF Document (.pdf)',
                                'excel' => 'Excel Spreadsheet (.xlsx)',
                                'csv' => 'CSV Export (.csv)',
                            ])
                            ->default('pdf')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'completed' => 'Completed',
                                'pending' => 'Pending Generation',
                            ])
                            ->default('completed'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('format')
                    ->label('Format')
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Generated At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'financial' => 'Financial',
                        'campaign' => 'Campaign',
                        'inventory' => 'Inventory',
                        'reservation' => 'Reservation',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->action(fn () => null),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
            'create' => Pages\CreateReport::route('/create'),
        ];
    }
}
