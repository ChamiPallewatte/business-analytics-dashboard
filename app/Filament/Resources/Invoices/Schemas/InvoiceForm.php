<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Models\Invoice;
use App\Models\Client;
use App\Models\Service;
use App\Models\Setting;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid as SchemaGrid;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;
use Carbon\Carbon;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                SchemaGrid::make([
                    'default' => 1,
                    'lg' => 3,
                ])
                ->schema([
                    // Left Column (2/3 width)
                    SchemaGrid::make(1)
                        ->schema([
                            SchemaSection::make('Invoice Information')
                                ->schema([
                                    SchemaGrid::make(3)->schema([
                                        Select::make('client_id')
                                            ->relationship('client', 'company_name')
                                            ->label('Client Name')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->reactive()
                                            ->afterStateUpdated(function (callable $set, $state) {
                                                $set('service_id', null);
                                                if ($state) {
                                                    $client = Client::find($state);
                                                    if ($client) {
                                                        $currency = Setting::getCountryCurrency($client->country);
                                                        $set('currency', $currency);
                                                        $vatPercent = Setting::getCountryVatPercent($client->country);
                                                        $set('vat_percent', $vatPercent);
                                                    }
                                                }
                                            }),

                                        TextInput::make('invoice_number')
                                            ->label('Invoice Number')
                                            ->placeholder('Auto-Generated')
                                            ->disabled()
                                            ->dehydrated(false),

                                        TextInput::make('reference_number')
                                            ->label('PO / Reference No.')
                                            ->maxLength(255),
                                    ]),

                                    SchemaGrid::make(3)->schema([
                                        DatePicker::make('invoice_date')
                                            ->label('Invoice Date')
                                            ->native(false)
                                            ->default(now())
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if ($state) {
                                                    $date = Carbon::parse($state);
                                                    $set('vat_period', $date->format('F Y'));
                                                    $set('vat_due_month', Carbon::parse($state)->addMonth()->format('F Y'));
                                                }
                                            }),

                                        DatePicker::make('due_date')
                                            ->label('Due Date')
                                            ->native(false)
                                            ->default(now()->addDays(14))
                                            ->required(),

                                        Select::make('currency')
                                            ->label('Currency')
                                            ->options([
                                                'AED' => 'AED - UAE Dirham',
                                                'SAR' => 'SAR - Saudi Riyal',
                                                'OMR' => 'OMR - Oman Rial',
                                                'BHD' => 'BHD - Bahrain Dinar',
                                                'QAR' => 'QAR - Qatar Riyal',
                                                'KWD' => 'KWD - Kuwaiti Dinar',
                                                'USD' => 'USD - US Dollar',
                                                'GBP' => 'GBP - British Pound',
                                            ])
                                            ->default('AED')
                                            ->required()
                                            ->reactive(),
                                    ]),

                                    SchemaGrid::make(3)->schema([
                                        Select::make('payment_terms')
                                            ->label('Payment Terms')
                                            ->options(Invoice::$terms)
                                            ->default('One-Time')
                                            ->required(),

                                        Select::make('status')
                                            ->label('Status')
                                            ->options(Invoice::$statuses)
                                            ->default('Draft')
                                            ->required(),
                                    ]),
                                ]),

                            SchemaSection::make('Service Details')
                                ->schema([
                                    SchemaGrid::make(2)->schema([
                                        Select::make('service_id')
                                            ->label('Linked Service')
                                            ->options(function (callable $get) {
                                                $clientId = $get('client_id');
                                                if (!$clientId) {
                                                    return Service::pluck('type', 'id');
                                                }
                                                return Service::where('client_id', $clientId)->pluck('type', 'id');
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->nullable(),

                                        TextInput::make('billing_period')
                                            ->label('Billing Period')
                                            ->placeholder('e.g. 01 May 2025 - 31 May 2025')
                                            ->maxLength(255),
                                    ]),

                                    Textarea::make('remarks')
                                        ->label('Service Description / Remarks')
                                        ->rows(2),
                                ]),

                            SchemaSection::make('Invoice Items')
                                ->schema([
                                    Repeater::make('items')
                                        ->label('Items')
                                        ->schema([
                                            TextInput::make('description')
                                                ->label('Item / Description')
                                                ->required()
                                                ->columnSpan(2),

                                            Select::make('service_type')
                                                ->label('Service Type')
                                                ->options(Service::$types)
                                                ->required()
                                                ->columnSpan(1),

                                            TextInput::make('qty')
                                                ->label('Qty')
                                                ->numeric()
                                                ->default(1)
                                                ->required()
                                                ->reactive()
                                                ->columnSpan(1)
                                                ->afterStateUpdated(fn ($state, callable $set, callable $get) => self::updateItemTotal($set, $get)),

                                            TextInput::make('unit_price')
                                                ->label('Unit Price')
                                                ->numeric()
                                                ->required()
                                                ->reactive()
                                                ->columnSpan(1)
                                                ->prefix(fn (callable $get) => $get('../../currency') ?? 'AED')
                                                ->afterStateUpdated(fn ($state, callable $set, callable $get) => self::updateItemTotal($set, $get)),

                                            TextInput::make('amount')
                                                ->label('Amount')
                                                ->numeric()
                                                ->disabled()
                                                ->dehydrated()
                                                ->required()
                                                ->columnSpan(1)
                                                ->prefix(fn (callable $get) => $get('../../currency') ?? 'AED'),
                                        ])
                                        ->columns(6)
                                        ->default([
                                            ['description' => '', 'service_type' => 'Website Development', 'qty' => 1, 'unit_price' => 0, 'amount' => 0]
                                        ])
                                        ->reactive()
                                        ->afterStateUpdated(fn (callable $set, callable $get) => self::updateTotals($set, $get))
                                        ->createItemButtonLabel('Add Item'),
                                ]),

                            SchemaSection::make('VAT Information')
                                ->schema([
                                    SchemaGrid::make(4)->schema([
                                        Select::make('vat_type')
                                            ->label('VAT Type')
                                            ->options([
                                                'Standard' => 'Standard Rated (5%)',
                                                'Zero' => 'Zero Rated (0%)',
                                                'Exempt' => 'Exempt (0%)',
                                                'Custom' => 'Custom Rate',
                                            ])
                                            ->default('Standard')
                                            ->reactive()
                                            ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                                if ($state === 'Standard') {
                                                    $client = Client::find($get('client_id'));
                                                    $vatPercent = $client ? Setting::getCountryVatPercent($client->country) : 5.00;
                                                    $set('vat_percent', $vatPercent);
                                                } elseif ($state === 'Zero' || $state === 'Exempt') {
                                                    $set('vat_percent', 0.00);
                                                }
                                                self::updateTotals($set, $get);
                                            }),

                                        TextInput::make('vat_percent')
                                            ->label('VAT Percentage')
                                            ->numeric()
                                            ->default(5.00)
                                            ->suffix('%')
                                            ->reactive()
                                            ->afterStateUpdated(fn (callable $set, callable $get) => self::updateTotals($set, $get)),

                                        TextInput::make('vat_period')
                                            ->label('VAT Period')
                                            ->default(now()->format('F Y'))
                                            ->placeholder('e.g. May 2025')
                                            ->required()
                                            ->reactive(),

                                        TextInput::make('vat_due_month')
                                            ->label('VAT Due Month')
                                            ->default(now()->addMonth()->format('F Y'))
                                            ->placeholder('e.g. June 2025')
                                            ->required()
                                            ->reactive(),
                                    ]),
                                ]),
                        ])
                        ->columnSpan(['lg' => 2]),

                    // Right Column (1/3 width)
                    SchemaGrid::make(1)
                        ->schema([
                            SchemaSection::make('Invoice Summary')
                                ->schema([
                                    Placeholder::make('summary_subtotal')
                                        ->label('Sub Total (Excl. VAT)')
                                        ->content(fn (callable $get) => ($get('currency') ?? 'AED') . ' ' . number_format(floatval($get('amount') ?? 0), 2)),

                                    Placeholder::make('summary_vat')
                                        ->label(fn (callable $get) => 'VAT (' . number_format(floatval($get('vat_percent') ?? 5), 1) . '%)')
                                        ->content(fn (callable $get) => ($get('currency') ?? 'AED') . ' ' . number_format(floatval($get('vat_amount') ?? 0), 2)),

                                    Placeholder::make('summary_total')
                                        ->label('Total Amount (Incl. VAT)')
                                        ->content(fn (callable $get) => ($get('currency') ?? 'AED') . ' ' . number_format(floatval($get('total_amount') ?? 0), 2)),
                                ]),

                            SchemaSection::make('VAT Summary')
                                ->schema([
                                    Placeholder::make('vat_summary_period')
                                        ->label('VAT Period')
                                        ->content(fn (callable $get) => $get('vat_period') ?: 'Not set'),

                                    Placeholder::make('vat_summary_due_month')
                                        ->label('VAT Due Month')
                                        ->content(fn (callable $get) => $get('vat_due_month') ?: 'Not set'),

                                    Placeholder::make('vat_summary_vatable')
                                        ->label('VATable Amount')
                                        ->content(fn (callable $get) => ($get('currency') ?? 'AED') . ' ' . number_format(floatval($get('amount') ?? 0), 2)),

                                    Placeholder::make('vat_summary_vat_amount')
                                        ->label('VAT Amount')
                                        ->content(fn (callable $get) => ($get('currency') ?? 'AED') . ' ' . number_format(floatval($get('vat_amount') ?? 0), 2)),

                                    Placeholder::make('vat_summary_vat_due')
                                        ->label('VAT Due')
                                        ->content(fn (callable $get) => ($get('currency') ?? 'AED') . ' ' . number_format(floatval($get('vat_amount') ?? 0), 2)),
                                ]),

                            SchemaSection::make('Other Information')
                                ->schema([
                                    Placeholder::make('created_by')
                                        ->label('Created By')
                                        ->content(fn () => auth()->user()->name),

                                    TextInput::make('project_campaign')
                                        ->label('Project / Campaign')
                                        ->placeholder('Select project')
                                        ->nullable(),

                                    Select::make('department')
                                        ->label('Department')
                                        ->options([
                                            'Development' => 'Development',
                                            'Marketing' => 'Marketing',
                                            'Sales' => 'Sales',
                                            'Design' => 'Design',
                                            'Administration' => 'Administration',
                                        ])
                                        ->placeholder('Select department')
                                        ->nullable(),

                                    FileUpload::make('attachments')
                                        ->label('Attachments')
                                        ->directory('invoice-attachments')
                                        ->multiple()
                                        ->preserveFilenames()
                                        ->nullable(),
                                ]),
                        ])
                        ->columnSpan(['lg' => 1]),
                ]),
            ]);
    }

    public static function updateItemTotal(callable $set, callable $get): void
    {
        $qty = floatval($get('qty') ?? 1);
        $price = floatval($get('unit_price') ?? 0);
        $set('amount', $qty * $price);
    }

    public static function updateTotals(callable $set, callable $get): void
    {
        $items = $get('items') ?? [];
        $subtotal = 0;
        foreach ($items as $item) {
            $qty = floatval($item['qty'] ?? 1);
            $price = floatval($item['unit_price'] ?? 0);
            $subtotal += $qty * $price;
        }

        $set('amount', $subtotal);

        $percent = floatval($get('vat_percent') ?? 5.00);
        $vat = round($subtotal * ($percent / 100), 2);
        $total = round($subtotal + $vat, 2);

        $set('vat_amount', $vat);
        $set('total_amount', $total);
    }
}
