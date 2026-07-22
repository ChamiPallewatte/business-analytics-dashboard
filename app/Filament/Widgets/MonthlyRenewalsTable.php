<?php

namespace App\Filament\Widgets;

use App\Models\Service;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class MonthlyRenewalsTable extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function getTableRecordKey(\Illuminate\Database\Eloquent\Model|array $record): string
    {
        return (string) ($record instanceof \Illuminate\Database\Eloquent\Model ? $record->month_key : $record['month_key']);
    }

    public ?int $clientId = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Service::query()
                    ->selectRaw("DATE_FORMAT(renewal_date, '%Y-%m') as month_key, DATE_FORMAT(renewal_date, '%M %Y') as month, count(*) as count, sum(total_amount) as total_value, sum(case when payment_status = 'paid' then 1 else 0 end) as renewed_count, sum(case when payment_status != 'paid' then 1 else 0 end) as pending_count")
                    ->where('service_status', '!=', 'cancelled')
                    ->when(auth()->user()->role === 'staff', fn ($q) => $q->whereHas('client', fn ($cq) => $cq->where('assigned_manager_id', auth()->id())))
                    ->when($this->clientId, fn ($q) => $q->where('client_id', $this->clientId))
                    ->groupBy('month_key', 'month')
                    ->orderBy('month_key', 'asc')
            )
            ->heading('Monthly Renewal Projections')
            ->description('Renewal pipeline value and statuses grouped by month.')
            ->paginated(false)
            ->defaultSort('month_key', 'asc')
            ->defaultKeySort(false)
            ->columns([
                TextColumn::make('month')
                    ->label('Renewal Month')
                    ->weight('bold'),

                TextColumn::make('count')
                    ->label('Accounts Renewing')
                    ->alignCenter(),

                TextColumn::make('total_value')
                    ->label('Renewal Value')
                    ->money('AED'),

                TextColumn::make('renewed_count')
                    ->label('Fully Paid Accounts')
                    ->color('success')
                    ->alignCenter(),

                TextColumn::make('pending_count')
                    ->label('Pending Invoices')
                    ->color('danger')
                    ->alignCenter(),
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
        $filename = "report_monthly_renewals_" . now()->format('YmdHis') . ".csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Renewal Month', 'Accounts Renewing', 'Renewal Value ($)', 'Fully Paid Accounts', 'Pending Invoices']);

            $user = auth()->user();
            $renewals = Service::query()
                ->selectRaw("DATE_FORMAT(renewal_date, '%Y-%m') as month_key, DATE_FORMAT(renewal_date, '%M %Y') as month, count(*) as count, sum(total_amount) as total_value, sum(case when payment_status = 'paid' then 1 else 0 end) as renewed_count, sum(case when payment_status != 'paid' then 1 else 0 end) as pending_count")
                ->where('service_status', '!=', 'cancelled')
                ->when($user->role === 'staff', fn ($q) => $q->whereHas('client', fn ($cq) => $cq->where('assigned_manager_id', $user->id)))
                ->when($this->clientId, fn ($q) => $q->where('client_id', $this->clientId))
                ->groupBy('month_key', 'month')
                ->orderBy('month_key', 'asc')
                ->get();

            foreach ($renewals as $row) {
                fputcsv($file, [
                    $row->month,
                    $row->count,
                    $row->total_value ?? 0,
                    $row->renewed_count,
                    $row->pending_count
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
        $renewals = Service::query()
            ->selectRaw("DATE_FORMAT(renewal_date, '%Y-%m') as month_key, DATE_FORMAT(renewal_date, '%M %Y') as month, count(*) as count, sum(total_amount) as total_value, sum(case when payment_status = 'paid' then 1 else 0 end) as renewed_count, sum(case when payment_status != 'paid' then 1 else 0 end) as pending_count")
            ->where('service_status', '!=', 'cancelled')
            ->when($user->role === 'staff', fn ($q) => $q->whereHas('client', fn ($cq) => $cq->where('assigned_manager_id', $user->id)))
            ->when($this->clientId, fn ($q) => $q->where('client_id', $this->clientId))
            ->groupBy('month_key', 'month')
            ->orderBy('month_key', 'asc')
            ->get()
            ->map(fn($row) => [
                'month' => $row->month,
                'count' => $row->count,
                'total_value' => $row->total_value ?? 0,
                'renewed_count' => $row->renewed_count,
                'pending_count' => $row->pending_count,
            ])
            ->toArray();

        $pdf = Pdf::loadView('pdf.report', [
            'title' => 'Monthly Renewal Forecast',
            'reportType' => 'monthly_renewals',
            'data' => $renewals,
            'date' => now()->format('F d, Y H:i:s'),
            'user' => $user,
        ]);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "report_monthly_renewals_" . now()->format('YmdHis') . ".pdf"
        );
    }
}
