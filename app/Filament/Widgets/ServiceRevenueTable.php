<?php

namespace App\Filament\Widgets;

use App\Models\Service;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class ServiceRevenueTable extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getTableRecordKey(\Illuminate\Database\Eloquent\Model|array $record): string
    {
        return (string) ($record instanceof \Illuminate\Database\Eloquent\Model ? $record->type : $record['type']);
    }

    public ?int $clientId = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Service::query()
                    ->selectRaw('type, count(*) as count, sum(service_value) as revenue, sum(total_amount) as total_with_vat, sum(paid_amount) as paid, sum(balance_amount) as balance')
                    ->where('service_status', '!=', 'cancelled')
                    ->when(auth()->user()->role === 'staff', fn ($q) => $q->whereHas('client', fn ($cq) => $cq->where('assigned_manager_id', auth()->id())))
                    ->when($this->clientId, fn ($q) => $q->where('client_id', $this->clientId))
                    ->groupBy('type')
            )
            ->heading('Service-wise Revenue Analysis')
            ->description('Distribution of contract values and collected payments by service type.')
            ->paginated(false) // Only 9 types, no pagination needed
            ->defaultSort('type', 'asc')
            ->defaultKeySort(false)
            ->columns([
                TextColumn::make('type')
                    ->label('Service Type')
                    ->weight('bold'),

                TextColumn::make('count')
                    ->label('Active Subscriptions')
                    ->alignCenter(),

                TextColumn::make('revenue')
                    ->label('Value (excl. VAT)')
                    ->money('AED'),

                TextColumn::make('total_with_vat')
                    ->label('Total (incl. VAT)')
                    ->money('AED'),

                TextColumn::make('paid')
                    ->label('Collected Amount')
                    ->money('AED')
                    ->color('success'),

                TextColumn::make('balance')
                    ->label('Outstanding Dues')
                    ->money('AED')
                    ->color('danger')
                    ->weight('bold'),
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
            ]);
    }

    /**
     * Export report data to CSV.
     */
    public function exportCsv()
    {
        $filename = "report_service_revenue_" . now()->format('YmdHis') . ".csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Service Type', 'Active Services', 'Service Value ($)', 'Total with VAT ($)', 'Paid Amount ($)', 'Balance Outstanding ($)']);

            $user = auth()->user();
            $services = Service::query()
                ->selectRaw('type, count(*) as count, sum(service_value) as revenue, sum(total_amount) as total_with_vat, sum(paid_amount) as paid, sum(balance_amount) as balance')
                ->where('service_status', '!=', 'cancelled')
                ->when($user->role === 'staff', fn ($q) => $q->whereHas('client', fn ($cq) => $cq->where('assigned_manager_id', $user->id)))
                ->when($this->clientId, fn ($q) => $q->where('client_id', $this->clientId))
                ->groupBy('type')
                ->get();

            foreach ($services as $row) {
                fputcsv($file, [
                    $row->type,
                    $row->count,
                    $row->revenue ?? 0,
                    $row->total_with_vat ?? 0,
                    $row->paid ?? 0,
                    $row->balance ?? 0
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
            ->selectRaw('type, count(*) as count, sum(service_value) as revenue, sum(total_amount) as total_with_vat, sum(paid_amount) as paid, sum(balance_amount) as balance')
            ->where('service_status', '!=', 'cancelled')
            ->when($user->role === 'staff', fn ($q) => $q->whereHas('client', fn ($cq) => $cq->where('assigned_manager_id', $user->id)))
            ->when($this->clientId, fn ($q) => $q->where('client_id', $this->clientId))
            ->groupBy('type')
            ->get()
            ->map(fn($row) => [
                'type' => $row->type,
                'count' => $row->count,
                'revenue' => $row->revenue ?? 0,
                'total_with_vat' => $row->total_with_vat ?? 0,
                'paid' => $row->paid ?? 0,
                'balance' => $row->balance ?? 0,
            ])
            ->toArray();

        $pdf = Pdf::loadView('pdf.report', [
            'title' => 'Service-wise Revenue Analysis',
            'reportType' => 'service_revenue',
            'data' => $services,
            'date' => now()->format('F d, Y H:i:s'),
            'user' => $user,
        ]);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "report_service_revenue_" . now()->format('YmdHis') . ".pdf"
        );
    }
}
