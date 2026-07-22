<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Models\Service;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Service Setup')
                    ->description('Select the client and service category.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('client_id')
                                ->relationship('client', 'name')
                                ->label('Client')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->visible(fn ($livewire) => !($livewire instanceof \Filament\Resources\RelationManagers\RelationManager)),

                            Select::make('type')
                                ->label('Service Type')
                                ->options(Service::$types)
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state === 'Hosting' || $state === 'Domain') {
                                        $set('service_status', 'active');
                                    } else {
                                        $set('service_status', 'pending');
                                    }
                                }),
                        ]),
                    ]),

                Section::make('Timeline')
                    ->description('Track service durations and renewal deadlines.')
                    ->schema([
                        Grid::make(3)->schema([
                            DatePicker::make('start_date')
                                ->label('Start Date')
                                ->native(false)
                                ->required(),

                            DatePicker::make('end_date')
                                ->label('End Date')
                                ->native(false)
                                ->required(),

                            DatePicker::make('renewal_date')
                                ->label('Renewal Date')
                                ->native(false)
                                ->required(),
                        ]),
                    ]),

                Section::make('Pricing & Financials')
                    ->description('Input values to compute VAT and balance details in real-time.')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('service_value')
                                ->label('Service Value')
                                ->numeric()
                                ->required()
                                ->prefix('AED')
                                ->reactive()
                                ->afterStateUpdated(fn ($state, callable $set, callable $get) => self::updateTotals($set, $get)),

                            TextInput::make('vat_percent')
                                ->label('VAT %')
                                ->numeric()
                                ->default(5.00)
                                ->prefix('%')
                                ->reactive()
                                ->afterStateUpdated(fn ($state, callable $set, callable $get) => self::updateTotals($set, $get)),

                            TextInput::make('vat_amount')
                                ->label('VAT Amount')
                                ->numeric()
                                ->disabled()
                                ->dehydrated()
                                ->prefix('AED'),
                        ]),

                        Grid::make(3)->schema([
                            TextInput::make('total_amount')
                                ->label('Total Amount')
                                ->numeric()
                                ->disabled()
                                ->dehydrated()
                                ->prefix('AED'),

                            TextInput::make('paid_amount')
                                ->label('Paid Amount')
                                ->numeric()
                                ->disabled() // Paid amount is logged via payments relation manager
                                ->dehydrated()
                                ->default(0.00)
                                ->prefix('AED'),

                            TextInput::make('balance_amount')
                                ->label('Balance Amount')
                                ->numeric()
                                ->disabled()
                                ->dehydrated()
                                ->prefix('AED'),
                        ]),
                    ]),

                Section::make('Status')
                    ->description('Select the service status and payment status.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('vat_paid_status')
                                ->label('VAT Paid Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'paid' => 'Paid',
                                ])
                                ->default('pending')
                                ->required(),

                            Select::make('service_status')
                                ->label('Service Status')
                                ->options(function (callable $get) {
                                    $type = $get('type');
                                    if ($type === 'Hosting' || $type === 'Domain') {
                                        return [
                                            'active' => 'Active',
                                            'suspended' => 'Suspended',
                                            'expired' => 'Expired',
                                            'cancelled' => 'Cancelled',
                                        ];
                                    }
                                    return [
                                        'pending' => 'Pending',
                                        'in_progress' => 'In Progress',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                    ];
                                })
                                ->required(),
                        ]),
                    ]),
            ]);
    }

    /**
     * Compute totals dynamically based on inputs.
     */
    public static function updateTotals(callable $set, callable $get): void
    {
        $value = floatval($get('service_value') ?? 0);
        $percent = floatval($get('vat_percent') ?? 5.00);
        $paid = floatval($get('paid_amount') ?? 0);

        $vat = round($value * ($percent / 100), 2);
        $total = round($value + $vat, 2);
        $balance = round(max(0, $total - $paid), 2);

        $set('vat_amount', $vat);
        $set('total_amount', $total);
        $set('balance_amount', $balance);
    }
}
