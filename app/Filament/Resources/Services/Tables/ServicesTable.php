<?php

namespace App\Filament\Resources\Services\Tables;

use App\Models\Service;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Service Type')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('end_date')
                    ->label('End Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('renewal_date')
                    ->label('Renewal Date')
                    ->date()
                    ->sortable()
                    ->color(fn (Service $record): string => 
                        $record->renewal_date->isPast() ? 'danger' : 
                        ($record->renewal_date->diffInDays(now()) <= 30 ? 'warning' : 'gray')
                    ),

                TextColumn::make('service_value')
                    ->label('Value')
                    ->money('AED')
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total (incl. VAT)')
                    ->money('AED')
                    ->sortable(),

                TextColumn::make('paid_amount')
                    ->label('Paid')
                    ->money('AED')
                    ->sortable(),

                TextColumn::make('balance_amount')
                    ->label('Balance')
                    ->money('AED')
                    ->sortable()
                    ->color(fn (Service $record): string => $record->balance_amount > 0 ? 'danger' : 'success')
                    ->weight('bold'),

                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'partially_paid' => 'warning',
                        'unpaid' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid' => 'Paid',
                        'partially_paid' => 'Partial',
                        'unpaid' => 'Unpaid',
                        default => ucfirst($state),
                    }),

                TextColumn::make('service_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'completed' => 'success',
                        'expired' => 'danger',
                        'suspended' => 'warning',
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in_progress' => 'In Progress',
                        default => ucfirst($state),
                    }),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Service Type')
                    ->options(Service::$types),

                SelectFilter::make('service_status')
                    ->label('Service Status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'expired' => 'Expired',
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'partially_paid' => 'Partially Paid',
                        'paid' => 'Paid',
                    ]),

                Filter::make('upcoming_renewals')
                    ->label('Upcoming Renewals (30 Days)')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereBetween('renewal_date', [now(), now()->addDays(30)])
                    ),

                Filter::make('overdue_payments')
                    ->label('Overdue Payments')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('balance_amount', '>', 0)
                              ->where('end_date', '<', now())
                    ),
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
