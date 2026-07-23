<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Company;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static \UnitEnum|string|null $navigationGroup = 'User & Access Management';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Account Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(User::class, 'email', ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->decompress(false)
                            ->required(fn ($livewire) => $livewire instanceof Pages\CreateUser)
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('Organization & RBAC Permissions')
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->label('Assigned Company')
                            ->relationship('company', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn () => auth()->user()->isSuperAdmin()),
                        Forms\Components\Select::make('role')
                            ->label('User Role')
                            ->options([
                                'super_admin' => 'Super Admin (Platform Owner)',
                                'company_admin' => 'Company Admin (Workspace Owner)',
                                'employee' => 'Employee (Standard Access)',
                            ])
                            ->required()
                            ->default('employee'),
                        Forms\Components\TextInput::make('department')
                            ->placeholder('e.g. Sales, Marketing, IT'),
                        Forms\Components\TextInput::make('position')
                            ->placeholder('e.g. Senior Manager, Analyst'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'suspended' => 'Suspended',
                                'inactive' => 'Inactive',
                            ])
                            ->default('active')
                            ->required(),
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
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Tables\Columns\TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'company_admin', 'admin' => 'warning',
                        default => 'success',
                    }),
                Tables\Columns\TextColumn::make('department')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'suspended' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'super_admin' => 'Super Admin',
                        'company_admin' => 'Company Admin',
                        'employee' => 'Employee',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
