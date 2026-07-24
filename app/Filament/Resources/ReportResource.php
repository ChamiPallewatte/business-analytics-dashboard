<?php

namespace App\Filament\Resources;

use App\Actions\DownloadReportAction;
use App\Filament\Resources\ReportResource\Pages;
use App\Models\Report;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
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
                                'financial'   => 'Financial Performance Summary',
                                'campaign'    => 'Digital Marketing Campaign ROI',
                                'inventory'   => 'Stock & Inventory Analytics',
                                'reservation' => 'Orders & Reservations Report',
                                'general'     => 'Executive KPI Overview',
                            ])
                            ->required(),
                        Forms\Components\Select::make('format')
                            ->options([
                                'pdf'   => 'PDF Document (.pdf)',
                                'excel' => 'Excel Spreadsheet (.xlsx)',
                                'csv'   => 'CSV Export (.csv)',
                            ])
                            ->default('pdf')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'completed' => 'Completed',
                                'pending'   => 'Pending Generation',
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
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->badge()
                    ->color('info')
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'financial'   => 'Financial',
                        'campaign'    => 'Campaign ROI',
                        'inventory'   => 'Inventory',
                        'reservation' => 'Orders/Reservations',
                        default       => 'Executive KPI',
                    })
                    ->color('info'),
                Tables\Columns\TextColumn::make('format')
                    ->label('Format')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => strtoupper($state))
                    ->color(fn (string $state) => match ($state) {
                        'pdf'   => 'danger',
                        'excel' => 'success',
                        'csv'   => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'completed'  => 'success',
                        'pending'    => 'warning',
                        'processing' => 'info',
                        default      => 'gray',
                    }),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Created By')
                    ->placeholder('System')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Generated At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'financial'   => 'Financial',
                        'campaign'    => 'Campaign',
                        'inventory'   => 'Inventory',
                        'reservation' => 'Reservation',
                        'general'     => 'General KPI',
                    ]),
                Tables\Filters\SelectFilter::make('format')
                    ->options([
                        'pdf'   => 'PDF',
                        'excel' => 'Excel',
                        'csv'   => 'CSV',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'completed'  => 'Completed',
                        'pending'    => 'Pending',
                        'processing' => 'Processing',
                    ]),
            ])
            ->actions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->tooltip(fn (Report $record) => 'Download as ' . strtoupper($record->format))
                    ->visible(fn (Report $record) => $record->status === 'completed')
                    ->action(fn (Report $record) => DownloadReportAction::handle($record)),

                Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->tooltip('Open report preview in browser')
                    ->visible(fn (Report $record) => $record->status === 'completed' && $record->format === 'pdf')
                    ->url(fn (Report $record) => route('reports.preview', $record))
                    ->openUrlInNewTab(),

                DeleteAction::make()
                    ->visible(fn () => auth()->user()?->isSuperAdmin() || auth()->user()?->isCompanyAdmin()),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListReports::route('/'),
            'create' => Pages\CreateReport::route('/create'),
        ];
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isSuperAdmin() || auth()->user()?->isCompanyAdmin();
    }
}
