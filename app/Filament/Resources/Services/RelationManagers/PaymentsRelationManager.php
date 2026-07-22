<?php

namespace App\Filament\Resources\Services\RelationManagers;

use App\Models\Payment;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('invoice_id')
                    ->label('Invoice Number')
                    ->options(fn ($livewire) => 
                        $livewire->getOwnerRecord() ? $livewire->getOwnerRecord()->invoices()->pluck('invoice_number', 'id')->toArray() : []
                    )
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $invoice = Invoice::find($state);
                        if ($invoice) {
                            $totalPaid = $invoice->payments()->sum('amount');
                            $remaining = max(0, $invoice->total_amount - $totalPaid);
                            $set('amount', $remaining);
                            $set('balance_amount', 0.00);
                            $set('status', '100% Paid');
                        }
                    }),
                    
                TextInput::make('amount')
                    ->label('Payment Received Amount')
                    ->numeric()
                    ->required()
                    ->prefix('AED')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $invoiceId = $get('invoice_id');
                        if ($invoiceId) {
                            $invoice = Invoice::find($invoiceId);
                            if ($invoice) {
                                $currentPaymentId = $get('id');
                                $query = $invoice->payments();
                                if ($currentPaymentId) {
                                    $query->where('id', '!=', $currentPaymentId);
                                }
                                $otherPaymentsSum = $query->sum('amount');
                                $amountPaid = floatval($state ?? 0);
                                $totalPaid = $otherPaymentsSum + $amountPaid;
                                
                                $balance = max(0, $invoice->total_amount - $totalPaid);
                                $set('balance_amount', $balance);
                                
                                if ($balance <= 0) {
                                    $set('status', '100% Paid');
                                } elseif (abs($totalPaid - ($invoice->total_amount * 0.5)) < 0.01) {
                                    $set('status', '50% Paid');
                                } elseif ($totalPaid > 0) {
                                    $set('status', 'Partial');
                                } else {
                                    $set('status', 'Pending');
                                }
                            }
                        }
                    }),

                DatePicker::make('payment_date')
                    ->label('Payment Date')
                    ->native(false)
                    ->default(now())
                    ->required(),

                Select::make('payment_method')
                    ->label('Payment Mode')
                    ->options(Payment::$methods)
                    ->required(),

                TextInput::make('reference_number')
                    ->label('Reference Number')
                    ->nullable()
                    ->maxLength(255),

                TextInput::make('balance_amount')
                    ->label('Balance Amount')
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->prefix('AED'),

                Select::make('status')
                    ->label('Payment Status')
                    ->options(Payment::$statuses)
                    ->required()
                    ->default('100% Paid'),

                Textarea::make('notes')
                    ->label('Remarks')
                    ->rows(2)
                    ->nullable()
                    ->maxLength(500),
            ]);
    }
 
     public function table(Table $table): Table
     {
         return $table
             ->recordTitleAttribute('amount')
             ->columns([
                TextColumn::make('invoice.invoice_number')
                    ->label('Invoice Number')
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Payment Received Amount')
                    ->money('AED')
                    ->sortable()
                    ->weight('bold'),
 
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
                     ->toggleable(isToggledHiddenByDefault: false),
             ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Record Payment')
                    ->after(function () {
                        // Triggers live refresh of the page to show new totals
                        $this->dispatch('refreshParent');
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->after(function () {
                        $this->dispatch('refreshParent');
                    }),
                DeleteAction::make()
                    ->after(function () {
                        $this->dispatch('refreshParent');
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
