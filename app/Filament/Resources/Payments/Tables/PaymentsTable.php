<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Models\Payment;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice.invoice_number')
                    ->label('Invoice Number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('invoice.client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->placeholder('General Payment'),

                TextColumn::make('amount')
                    ->label('Payment Received Amount')
                    ->money('AED')
                    ->sortable(),

                TextColumn::make('payment_date')
                    ->label('Payment Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label('Payment Mode')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Cash' => 'success',
                        'Bank Transfer' => 'info',
                        'Cheque' => 'warning',
                        'PDC' => 'primary',
                        'Card' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('reference_number')
                    ->label('Reference Number')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('balance_amount')
                    ->label('Balance Amount')
                    ->money('AED')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Payment Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '100% Paid' => 'success',
                        '50% Paid' => 'info',
                        'Partial' => 'warning',
                        'Pending' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('notes')
                    ->label('Remarks')
                    ->limit(50)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options(Payment::$methods),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
