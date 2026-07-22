<?php

namespace App\Filament\Resources\Clients\Schemas;

use App\Models\User;
use App\Models\Setting;
use App\Models\Service;
use Filament\Schemas\Components\Grid as SchemaGrid;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Schema;
use Carbon\Carbon;

class ClientForm
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
                    // Main column (2/3 width)
                    SchemaGrid::make(1)
                        ->schema([
                            SchemaSection::make('Company Information')
                                ->schema([
                                    SchemaGrid::make(3)->schema([
                                        TextInput::make('company_name')
                                            ->label('Company Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->reactive(),
                                        
                                        TextInput::make('trn')
                                            ->label('Trade License No. / TRN')
                                            ->nullable()
                                            ->maxLength(255),

                                        TextInput::make('email')
                                            ->label('Company Email')
                                            ->email()
                                            ->required()
                                            ->maxLength(255),
                                    ]),

                                    SchemaGrid::make(3)->schema([
                                        Select::make('business_type')
                                            ->label('Business Type')
                                            ->options([
                                                'LLC' => 'Limited Liability Company (LLC)',
                                                'Sole Establishment' => 'Sole Establishment',
                                                'Freezone' => 'Freezone Company',
                                                'Branch' => 'Branch of Foreign Company',
                                                'Other' => 'Other',
                                            ])
                                            ->nullable(),

                                        Select::make('industry')
                                            ->label('Industry')
                                            ->options([
                                                'Real Estate' => 'Real Estate',
                                                'E-commerce' => 'E-commerce',
                                                'Technology' => 'Technology',
                                                'Tourism & Hospitality' => 'Tourism & Hospitality',
                                                'Logistics' => 'Logistics',
                                                'Healthcare' => 'Healthcare',
                                                'Retail' => 'Retail',
                                                'Other' => 'Other',
                                            ])
                                            ->nullable(),

                                        TextInput::make('website')
                                            ->label('Company Website')
                                            ->url()
                                            ->placeholder('https://www.example.com')
                                            ->nullable()
                                            ->maxLength(255),
                                    ]),

                                    Textarea::make('address')
                                        ->label('Company Address')
                                        ->rows(2)
                                        ->required(),

                                    SchemaGrid::make(4)->schema([
                                        Select::make('country')
                                            ->label('Country')
                                            ->options([
                                                'United Arab Emirates' => 'United Arab Emirates',
                                                'Saudi Arabia' => 'Saudi Arabia',
                                                'Oman' => 'Oman',
                                                'Bahrain' => 'Bahrain',
                                                'Qatar' => 'Qatar',
                                                'Kuwait' => 'Kuwait',
                                                'United States' => 'United States',
                                                'United Kingdom' => 'United Kingdom',
                                            ])
                                            ->default('United Arab Emirates')
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function (callable $set, $state) {
                                                if ($state !== 'United Arab Emirates') {
                                                    $set('emirate', null);
                                                }
                                            }),

                                        Select::make('emirate')
                                            ->label('Emirate')
                                            ->options([
                                                'Abu Dhabi' => 'Abu Dhabi',
                                                'Dubai' => 'Dubai',
                                                'Sharjah' => 'Sharjah',
                                                'Ajman' => 'Ajman',
                                                'Umm Al Quwain' => 'Umm Al Quwain',
                                                'Ras Al Khaimah' => 'Ras Al Khaimah',
                                                'Fujairah' => 'Fujairah',
                                            ])
                                            ->placeholder('Select emirate')
                                            ->disabled(fn (callable $get) => $get('country') !== 'United Arab Emirates')
                                            ->reactive(),

                                        TextInput::make('city')
                                            ->label('City')
                                            ->required()
                                            ->maxLength(255),

                                        TextInput::make('postal_code')
                                            ->label('Postal Code')
                                            ->nullable()
                                            ->maxLength(50),
                                    ]),
                                ]),

                            SchemaSection::make('Primary Contact Information')
                                ->schema([
                                    SchemaGrid::make(3)->schema([
                                        TextInput::make('name')
                                            ->label('Contact Person Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->reactive(),

                                        TextInput::make('contact_designation')
                                            ->label('Designation')
                                            ->maxLength(255),

                                        TextInput::make('email_address')
                                            ->label('Email Address')
                                            ->email()
                                            ->maxLength(255),
                                    ]),

                                    SchemaGrid::make(3)->schema([
                                        SchemaGrid::make(3)->schema([
                                            Select::make('phone_code')
                                                ->label('Code')
                                                ->options([
                                                    '+971' => '🇦🇪 +971',
                                                    '+966' => '🇸🇦 +966',
                                                    '+968' => '🇴🇲 +968',
                                                    '+973' => '🇧🇭 +973',
                                                    '+974' => '🇶🇦 +974',
                                                    '+965' => '🇰🇼 +965',
                                                    '+1' => '🇺🇸 +1',
                                                    '+44' => '🇬🇧 +44',
                                                ])
                                                ->default('+971')
                                                ->required(),
                                            TextInput::make('mobile')
                                                ->label('Phone Number')
                                                ->required()
                                                ->columnSpan(2),
                                        ])->columnSpan(1),

                                        SchemaGrid::make(3)->schema([
                                            Select::make('alternate_phone_code')
                                                ->label('Code')
                                                ->options([
                                                    '+971' => '🇦🇪 +971',
                                                    '+966' => '🇸🇦 +966',
                                                    '+968' => '🇴🇲 +968',
                                                    '+973' => '🇧🇭 +973',
                                                    '+974' => '🇶🇦 +974',
                                                    '+965' => '🇰🇼 +965',
                                                    '+1' => '🇺🇸 +1',
                                                    '+44' => '🇬🇧 +44',
                                                ])
                                                ->default('+971'),
                                            TextInput::make('alternate_phone_number')
                                                ->label('Alternate Number')
                                                ->columnSpan(2),
                                        ])->columnSpan(1),

                                        SchemaGrid::make(3)->schema([
                                            Select::make('whatsapp_phone_code')
                                                ->label('Code')
                                                ->options([
                                                    '+971' => '🇦🇪 +971',
                                                    '+966' => '🇸🇦 +966',
                                                    '+968' => '🇴🇲 +968',
                                                    '+973' => '🇧🇭 +973',
                                                    '+974' => '🇶🇦 +974',
                                                    '+965' => '🇰🇼 +965',
                                                    '+1' => '🇺🇸 +1',
                                                    '+44' => '🇬🇧 +44',
                                                ])
                                                ->default('+971'),
                                            TextInput::make('whatsapp_phone_number')
                                                ->label('Whatsapp Number')
                                                ->columnSpan(2),
                                        ])->columnSpan(1),
                                    ]),
                                ]),

                            SchemaSection::make('Service Information')
                                ->schema([
                                    SchemaGrid::make(3)->schema([
                                        Select::make('service_type')
                                            ->label('Service Type')
                                            ->options(Service::$types)
                                            ->reactive(),

                                        Select::make('service_package_plan')
                                            ->label('Package / Plan')
                                            ->options([
                                                'Bronze' => 'Bronze Plan',
                                                'Silver' => 'Silver Plan',
                                                'Gold' => 'Gold Plan',
                                                'Custom' => 'Custom / Custom Plan',
                                            ])
                                            ->default('Custom')
                                            ->reactive(),

                                        Select::make('assigned_manager_id')
                                            ->label('Assigned Account Manager')
                                            ->options(User::pluck('name', 'id'))
                                            ->default(fn () => auth()->id())
                                            ->disabled(fn () => !auth()->user()->isAdmin())
                                            ->dehydrated()
                                            ->required(),
                                    ]),

                                    SchemaGrid::make(3)->schema([
                                        DatePicker::make('service_start_date')
                                            ->label('Start Date')
                                            ->native(false)
                                            ->reactive(),

                                        DatePicker::make('service_end_date')
                                            ->label('End Date')
                                            ->native(false)
                                            ->reactive(),

                                        DatePicker::make('service_renewal_date')
                                            ->label('Renewal Date (Optional)')
                                            ->native(false)
                                            ->reactive(),
                                    ]),

                                    Radio::make('service_billing_cycle')
                                        ->label('Billing Cycle')
                                        ->options([
                                            'Monthly' => 'Monthly',
                                            'Quarterly' => 'Quarterly',
                                            'Yearly' => 'Yearly',
                                        ])
                                        ->inline()
                                        ->default('Monthly')
                                        ->reactive(),

                                    Textarea::make('service_description')
                                        ->label('Service Description / Notes')
                                        ->rows(2),
                                ]),
                        ])
                        ->columnSpan(['lg' => 2]),

                    // Sidebar column (1/3 width)
                    SchemaGrid::make(1)
                        ->schema([
                            SchemaSection::make('Client Summary')
                                ->description('Review the details before saving')
                                ->schema([
                                    Placeholder::make('summary_company')
                                        ->label('Company')
                                        ->content(fn (callable $get) => $get('company_name') ?: 'Not provided'),

                                    Placeholder::make('summary_contact')
                                        ->label('Contact Person')
                                        ->content(fn (callable $get) => $get('name') ?: 'Not provided'),

                                    Placeholder::make('summary_service')
                                        ->label('Service Type')
                                        ->content(fn (callable $get) => $get('service_type') ?: 'Not selected'),

                                    Placeholder::make('summary_start_date')
                                        ->label('Start Date')
                                        ->content(fn (callable $get) => $get('service_start_date') ? Carbon::parse($get('service_start_date'))->format('d M Y') : 'Not selected'),

                                    Placeholder::make('summary_end_date')
                                        ->label('End Date')
                                        ->content(fn (callable $get) => $get('service_end_date') ? Carbon::parse($get('service_end_date'))->format('d M Y') : 'Not selected'),

                                    Placeholder::make('summary_renewal_date')
                                        ->label('Renewal Date')
                                        ->content(fn (callable $get) => $get('service_renewal_date') ? Carbon::parse($get('service_renewal_date'))->format('d M Y') : 'Not set'),
                                ]),

                            SchemaSection::make('Note')
                                ->schema([
                                    Placeholder::make('onboarding_notes')
                                        ->hiddenLabel()
                                        ->content(new \Illuminate\Support\HtmlString('
                                            <div class="text-sm text-gray-500 space-y-1">
                                                <p>After onboarding, you can:</p>
                                                <ul class="list-disc pl-4 space-y-0.5">
                                                    <li>Create invoices</li>
                                                    <li>Track payments</li>
                                                    <li>Manage services</li>
                                                    <li>View reports</li>
                                                </ul>
                                            </div>
                                        ')),
                                ]),
                        ])
                        ->columnSpan(['lg' => 1]),
                ]),
            ]);
    }
}
