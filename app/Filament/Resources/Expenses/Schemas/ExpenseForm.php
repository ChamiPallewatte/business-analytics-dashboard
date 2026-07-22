<?php

namespace App\Filament\Resources\Expenses\Schemas;

use App\Models\Expense;
use App\Models\Client;
use App\Models\Service;
use App\Models\Setting;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid as SchemaGrid;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;

class ExpenseForm
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
                            SchemaSection::make('Expense Target')
                                ->description('Select the client and service this expense is linked to.')
                                ->schema([
                                    SchemaGrid::make(2)->schema([
                                        Select::make('client_id')
                                            ->relationship('client', 'company_name')
                                            ->label('Client')
                                            ->searchable()
                                            ->preload()
                                            ->reactive()
                                            ->nullable()
                                            ->afterStateUpdated(function (callable $set, $state) {
                                                $set('service_id', null);
                                                if ($state) {
                                                    $client = Client::find($state);
                                                    if ($client) {
                                                        $currency = Setting::getCountryCurrency($client->country);
                                                        $set('currency', $currency);
                                                        $vatPercent = Setting::getCountryVatPercent($client->country);
                                                        $set('vat_percent', number_format($vatPercent, 2));
                                                    }
                                                }
                                            }),

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
                                    ]),
                                ]),

                            SchemaSection::make('Expense Information')
                                ->schema([
                                    SchemaGrid::make(3)->schema([
                                        DatePicker::make('expense_date')
                                            ->label('Expense Date')
                                            ->native(false)
                                            ->default(now())
                                            ->required(),

                                        Select::make('type')
                                            ->label('Expense Category')
                                            ->options(Expense::$types)
                                            ->required(),

                                        Select::make('payment_method')
                                            ->label('Payment Method')
                                            ->options([
                                                'Bank Transfer' => 'Bank Transfer',
                                                'Credit Card' => 'Credit Card',
                                                'Cash' => 'Cash',
                                                'Cheque' => 'Cheque',
                                                'Online' => 'Online Payment',
                                            ])
                                            ->default('Bank Transfer')
                                            ->required(),
                                    ]),

                                    SchemaGrid::make(3)->schema([
                                        TextInput::make('vendor_name')
                                            ->label('Vendor / Supplier')
                                            ->required()
                                            ->maxLength(255),

                                        TextInput::make('reference_number')
                                            ->label('Reference / Invoice No.')
                                            ->maxLength(255)
                                            ->nullable(),

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

                                    SchemaGrid::make(4)->schema([
                                        TextInput::make('amount')
                                            ->label('Amount (Excl. VAT)')
                                            ->numeric()
                                            ->required()
                                            ->reactive()
                                            ->prefix(fn (callable $get) => $get('currency') ?? 'AED')
                                            ->afterStateUpdated(fn (callable $set, callable $get) => self::updateTotals($set, $get)),

                                        Select::make('vat_percent')
                                            ->label('VAT (%)')
                                            ->options([
                                                '5.00' => '5% (Standard Rated)',
                                                '15.00' => '15% (Saudi Standard)',
                                                '10.00' => '10% (Bahrain Standard)',
                                                '0.00' => '0% (Zero/Exempt)',
                                            ])
                                            ->default('5.00')
                                            ->reactive()
                                            ->afterStateUpdated(fn (callable $set, callable $get) => self::updateTotals($set, $get)),

                                        TextInput::make('vat_amount')
                                            ->label('VAT Amount')
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated()
                                            ->prefix(fn (callable $get) => $get('currency') ?? 'AED'),

                                        TextInput::make('total_amount')
                                            ->label('Total Amount (Incl. VAT)')
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated()
                                            ->prefix(fn (callable $get) => $get('currency') ?? 'AED'),
                                    ]),
                                ]),

                            SchemaSection::make('Expense Details')
                                ->schema([
                                    Textarea::make('notes')
                                        ->label('Description')
                                        ->rows(2)
                                        ->required(),

                                    SchemaGrid::make(3)->schema([
                                        Select::make('department')
                                            ->label('Department')
                                            ->options([
                                                'Marketing' => 'Marketing',
                                                'Development' => 'Development',
                                                'Sales' => 'Sales',
                                                'Design' => 'Design',
                                                'Administration' => 'Administration',
                                            ])
                                            ->nullable(),

                                        TextInput::make('project')
                                            ->label('Project')
                                            ->maxLength(255)
                                            ->nullable(),

                                        TextInput::make('cost_center')
                                            ->label('Cost Center')
                                            ->maxLength(255)
                                            ->nullable(),
                                    ]),

                                    TextInput::make('tags')
                                        ->label('Tags')
                                        ->placeholder('e.g. hosting, domain, marketing')
                                        ->nullable(),
                                ]),

                            SchemaSection::make('Attachments')
                                ->schema([
                                    FileUpload::make('attachments')
                                        ->label('Attachments')
                                        ->directory('expense-attachments')
                                        ->multiple()
                                        ->preserveFilenames()
                                        ->nullable(),
                                ]),
                        ])
                        ->columnSpan(['lg' => 2]),

                    // Right Column (1/3 width)
                    SchemaGrid::make(1)
                        ->schema([
                            SchemaSection::make('Expense Summary')
                                ->schema([
                                    Placeholder::make('summary_excl')
                                        ->label('Amount (Excl. VAT)')
                                        ->content(fn (callable $get) => ($get('currency') ?? 'AED') . ' ' . number_format(floatval($get('amount') ?? 0), 2)),

                                    Placeholder::make('summary_vat')
                                        ->label('VAT Amount')
                                        ->content(fn (callable $get) => ($get('currency') ?? 'AED') . ' ' . number_format(floatval($get('vat_amount') ?? 0), 2)),

                                    Placeholder::make('summary_total')
                                        ->label('Total Amount (Incl. VAT)')
                                        ->content(fn (callable $get) => ($get('currency') ?? 'AED') . ' ' . number_format(floatval($get('total_amount') ?? 0), 2)),
                                ]),

                            SchemaSection::make('Category Breakdown (This Month)')
                                ->schema([
                                    Placeholder::make('category_chart')
                                        ->hiddenLabel()
                                        ->content(fn () => view('filament.components.expense-chart-placeholder')),
                                    
                                    Placeholder::make('chart_legend')
                                        ->hiddenLabel()
                                        ->content(new \Illuminate\Support\HtmlString('
                                            <div class="mt-2 text-xxs text-gray-400 text-center">
                                                Visual breakdown of expenses recorded this month
                                            </div>
                                        ')),
                                ]),

                            SchemaSection::make('Notes')
                                ->schema([
                                    Placeholder::make('notes_instruction')
                                        ->hiddenLabel()
                                        ->content(new \Illuminate\Support\HtmlString('
                                            <div class="text-sm text-gray-500 space-y-1.5">
                                                <p>✓ Expenses will be included in your profit & loss reports</p>
                                                <p>✓ Make sure to upload valid supporting documents</p>
                                                <p>✓ VAT will be reported in VAT Return for the selected period</p>
                                            </div>
                                        ')),
                                ]),
                        ])
                        ->columnSpan(['lg' => 1]),
                ]),
            ]);
    }

    public static function updateTotals(callable $set, callable $get): void
    {
        $amount = floatval($get('amount') ?? 0);
        $percent = floatval($get('vat_percent') ?? 5.00);

        $vat = round($amount * ($percent / 100), 2);
        $total = round($amount + $vat, 2);

        $set('vat_amount', $vat);
        $set('total_amount', $total);
    }
}
