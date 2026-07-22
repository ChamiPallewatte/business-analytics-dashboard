<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Models\Invoice;
use App\Models\Service;
use App\Models\Payment;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payment Allocation')
                    ->description('Select the invoice this payment is for.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('invoice_id')
                                ->label('Invoice')
                                ->relationship('invoice', 'invoice_number')
                                ->getOptionLabelFromRecordUsing(fn (Invoice $record) => "{$record->invoice_number} - " . ($record->client ? $record->client->name : 'No Client') . " (\${$record->total_amount})")
                                ->searchable()
                                ->preload()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $invoice = Invoice::find($state);
                                    if ($invoice) {
                                        $set('service_id', $invoice->service_id);
                                        $totalPaid = $invoice->payments()->sum('amount');
                                        $remaining = max(0, $invoice->total_amount - $totalPaid);
                                        $set('amount', $remaining);
                                    }
                                }),

                            Select::make('service_id')
                                ->label('Linked Service')
                                ->relationship('service', 'type')
                                ->disabled()
                                ->dehydrated()
                                ->nullable(),
                        ]),
                    ]),

                Section::make('Payment Details')
                    ->schema([
                        Grid::make(3)->schema([
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
                        ]),

                        Grid::make(3)->schema([
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
                        ]),

                        Grid::make(1)->schema([
                            Textarea::make('notes')
                                ->label('Remarks')
                                ->rows(3)
                                ->nullable(),
                        ]),
                    ]),
            ]);
    }
}
