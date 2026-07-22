<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Services\ServiceResource;
use App\Models\Service;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PendingPaymentsTable extends TableWidget
{
    protected static ?int $sort = 3; // Show third

    protected int|string|array $columnSpan = 'full'; // Span the full width of the dashboard

    public ?int $clientId = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Service::query()
                    ->where('balance_amount', '>', 0)
                    ->when(auth()->user()->role === 'staff', function ($q) {
                        $q->whereHas('client', function ($clientQ) {
                            $clientQ->where('assigned_manager_id', auth()->id());
                        });
                    })
                    ->when($this->clientId, fn ($q) => $q->where('client_id', $this->clientId))
                    ->orderBy('balance_amount', 'desc')
            )
            ->heading('Pending Payments & Overdue Balances')
            ->columns([
                TextColumn::make('client.name')
                    ->label('Client Name')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Service Type'),

                TextColumn::make('total_amount')
                    ->label('Total Value')
                    ->formatStateUsing(fn ($state) => \App\Models\Setting::getCurrency() . ' ' . number_format($state, 2)),

                TextColumn::make('paid_amount')
                    ->label('Paid')
                    ->formatStateUsing(fn ($state) => \App\Models\Setting::getCurrency() . ' ' . number_format($state, 2)),

                TextColumn::make('balance_amount')
                    ->label('Outstanding Balance')
                    ->formatStateUsing(fn ($state) => \App\Models\Setting::getCurrency() . ' ' . number_format($state, 2))
                    ->color('danger')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('Expiry Date')
                    ->date()
                    ->color(fn (Service $record): string => $record->end_date->isPast() ? 'danger' : 'gray')
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->label('Payment Status')
                    ->badge()
                    ->color(fn (string $state, Service $record): string => match (true) {
                        $record->balance_amount <= 0 => 'success',
                        $record->end_date->isPast() => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state, Service $record): string => match (true) {
                        $record->balance_amount <= 0 => 'Paid',
                        $record->end_date->isPast() => 'Overdue',
                        default => 'Due Soon',
                    }),
            ])
            ->headerActions([
                Action::make('export_csv')
                    ->label('Excel (CSV)')
                    ->icon('heroicon-m-document-arrow-down')
                    ->button()
                    ->color('gray')
                    ->action(fn () => $this->exportCsv()),

                Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-m-printer')
                    ->button()
                    ->color('danger')
                    ->action(fn () => $this->exportPdf()),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Record Payment / Edit')
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn (Service $record): string => ServiceResource::getUrl('edit', ['record' => $record])),
            ]);
    }

    /**
     * Export report data to CSV.
     */
    public function exportCsv()
    {
        $filename = "report_pending_payments_" . now()->format('YmdHis') . ".csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Client Name', 'Company Name', 'Service Type', 'Expiry Date', 'Total Value ($)', 'Paid Amount ($)', 'Outstanding Balance ($)']);

            $user = auth()->user();
            $services = Service::query()
                ->where('balance_amount', '>', 0)
                ->when($user->role === 'staff', function ($q) {
                    $q->whereHas('client', function ($clientQ) {
                        $clientQ->where('assigned_manager_id', auth()->id());
                    });
                })
                ->when($this->clientId, fn ($q) => $q->where('client_id', $this->clientId))
                ->orderBy('balance_amount', 'desc')
                ->get();

            foreach ($services as $service) {
                fputcsv($file, [
                    $service->client->name ?? 'N/A',
                    $service->client->company_name ?? 'N/A',
                    $service->type,
                    $service->end_date->format('Y-m-d'),
                    $service->total_amount,
                    $service->paid_amount,
                    $service->balance_amount
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export report data to PDF.
     */
    public function exportPdf()
    {
        $user = auth()->user();
        $services = Service::query()
            ->where('balance_amount', '>', 0)
            ->when($user->role === 'staff', function ($q) {
                $q->whereHas('client', function ($clientQ) {
                    $clientQ->where('assigned_manager_id', auth()->id());
                });
            })
            ->when($this->clientId, fn ($q) => $q->where('client_id', $this->clientId))
            ->orderBy('balance_amount', 'desc')
            ->get()
            ->map(fn($service) => [
                'client_name' => $service->client->name ?? 'N/A',
                'company_name' => $service->client->company_name ?? 'N/A',
                'type' => $service->type,
                'end_date' => $service->end_date->format('Y-m-d'),
                'total' => $service->total_amount,
                'paid' => $service->paid_amount,
                'balance' => $service->balance_amount,
            ])
            ->toArray();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.report', [
            'title' => 'Pending Payments & Overdue Dues',
            'reportType' => 'pending_payments',
            'data' => $services,
            'date' => now()->format('F d, Y H:i:s'),
            'user' => $user,
        ]);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "report_pending_payments_" . now()->format('YmdHis') . ".pdf"
        );
    }
}
