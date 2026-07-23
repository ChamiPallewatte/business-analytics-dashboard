<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static \UnitEnum|string|null $navigationGroup = 'Platform Administration';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Company Profile & Branding')
                    ->description('General organization settings and appearance')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug($state))),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->unique(Company::class, 'slug', ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Select::make('industry_type')
                            ->label('Industry Type')
                            ->required()
                            ->options([
                                'agency' => 'Digital Marketing Agency',
                                'restaurant' => 'Restaurant & Hospitality',
                                'retail' => 'Retail Store',
                                'healthcare' => 'Healthcare Clinic',
                                'real_estate' => 'Real Estate Company',
                                'ecommerce' => 'E-Commerce Business',
                                'manufacturing' => 'Manufacturing Company',
                                'education' => 'Educational Institution',
                                'general' => 'General Business',
                            ]),
                        Forms\Components\ColorPicker::make('primary_color')
                            ->label('Primary Brand Color')
                            ->default('#2563eb'),
                        Forms\Components\ColorPicker::make('secondary_color')
                            ->label('Secondary Brand Color')
                            ->default('#0f172a'),
                    ])->columns(2),

                Section::make('Subscription & Limits')
                    ->schema([
                        Forms\Components\Select::make('subscription_plan')
                            ->options([
                                'basic' => 'Basic Plan ($49/mo)',
                                'pro' => 'Professional Plan ($149/mo)',
                                'enterprise' => 'Enterprise Plan ($499/mo)',
                            ])
                            ->default('pro')
                            ->required(),
                        Forms\Components\Select::make('subscription_status')
                            ->options([
                                'active' => 'Active',
                                'suspended' => 'Suspended',
                                'trialing' => 'Trialing',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('active')
                            ->required(),
                        Forms\Components\TextInput::make('max_users')
                            ->numeric()
                            ->default(10)
                            ->required(),
                        Forms\Components\TextInput::make('storage_limit_mb')
                            ->label('Storage Limit (MB)')
                            ->numeric()
                            ->default(1000)
                            ->required(),
                    ])->columns(2),

                Section::make('Localization & Settings')
                    ->schema([
                        Forms\Components\Select::make('timezone')
                            ->options([
                                'UTC' => 'UTC',
                                'Asia/Dubai' => 'Dubai (GST - UTC+4)',
                                'America/New_York' => 'New York (EST)',
                                'Europe/London' => 'London (GMT/BST)',
                            ])
                            ->default('Asia/Dubai'),
                        Forms\Components\Select::make('currency')
                            ->options([
                                'USD' => 'USD ($)',
                                'AED' => 'AED (AED)',
                                'EUR' => 'EUR (€)',
                                'GBP' => 'GBP (£)',
                                'SAR' => 'SAR (SAR)',
                            ])
                            ->default('USD'),
                    ])->columns(2),
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
                Tables\Columns\TextColumn::make('industry_label')
                    ->label('Industry')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('subscription_plan')
                    ->label('Plan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'enterprise' => 'success',
                        'pro' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('subscription_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'suspended' => 'danger',
                        'trialing' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('industry_type')
                    ->options([
                        'agency' => 'Digital Marketing Agency',
                        'restaurant' => 'Restaurant',
                        'retail' => 'Retail Store',
                        'healthcare' => 'Healthcare Clinic',
                        'real_estate' => 'Real Estate',
                        'ecommerce' => 'E-Commerce',
                    ]),
                Tables\Filters\SelectFilter::make('subscription_status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('suspend')
                    ->label(fn (Company $record) => $record->isSuspended() ? 'Activate' : 'Suspend')
                    ->color(fn (Company $record) => $record->isSuspended() ? 'success' : 'danger')
                    ->icon(fn (Company $record) => $record->isSuspended() ? 'heroicon-o-check-circle' : 'heroicon-o-pause-circle')
                    ->action(function (Company $record) {
                        $record->update([
                            'subscription_status' => $record->isSuspended() ? 'active' : 'suspended',
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->isSuperAdmin();
    }
}
