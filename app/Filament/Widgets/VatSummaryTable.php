<?php

namespace App\Filament\Widgets;

use App\Models\Service;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class VatSummaryTable extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function getTableRecordKey(\Illuminate\Database\Eloquent\Model|array $record): string
    {
        return (string) ($record instanceof \Illuminate\Database\Eloquent\Model ? $record->vat_paid_status : $record['vat_paid_status']);
    }

    public ?int $clientId = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Service::query()
                    ->selectRaw('vat_paid_status, count(*) as count, sum(vat_amount) as amount')
                    ->where('service_status', '!=', 'cancelled')
                    ->when(auth()->user()->role === 'staff', fn ($q) => $q->whereHas('client', fn ($cq) => $cq->where('assigned_manager_id', auth()->id())))
                    ->when($this->clientId, fn ($q) => $q->where('client_id', $this->clientId))
                    ->groupBy('vat_paid_status')
            )
            ->heading('VAT Liability Summary')
            ->description('VAT amounts and collection statuses.')
            ->paginated(false)
            ->defaultSort('vat_paid_status', 'asc')
            ->defaultKeySort(false)
            ->columns([
                TextColumn::make('vat_paid_status')
                    ->label('VAT Paid Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid' => 'Paid VAT',
                        'pending' => 'Pending VAT',
                        default => ucfirst($state),
                    }),

                TextColumn::make('count')
                    ->label('Service Count')
                    ->alignCenter(),

                TextColumn::make('amount')
                    ->label('VAT Amount')
                    ->money('AED')
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
        $filename = "report_vat_summary_" . now()->format('YmdHis') . ".csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['VAT Paid Status', 'Service Count', 'VAT Amount ($)']);

            $user = auth()->user();
            $vatSummary = Service::query()
                ->selectRaw('vat_paid_status, count(*) as count, sum(vat_amount) as amount')
                ->where('service_status', '!=', 'cancelled')
                ->when($user->role === 'staff', fn ($q) => $q->whereHas('client', fn ($cq) => $cq->where('assigned_manager_id', $user->id)))
                ->when($this->clientId, fn ($q) => $q->where('client_id', $this->clientId))
                ->groupBy('vat_paid_status')
                ->get();

            foreach ($vatSummary as $row) {
                fputcsv($file, [
                    $row->vat_paid_status === 'paid' ? 'Paid VAT' : 'Pending VAT',
                    $row->count,
                    $row->amount ?? 0
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
        $vatData = Service::query()
            ->selectRaw('vat_paid_status, count(*) as count, sum(vat_amount) as amount')
            ->where('service_status', '!=', 'cancelled')
            ->when($user->role === 'staff', fn ($q) => $q->whereHas('client', fn ($cq) => $cq->where('assigned_manager_id', $user->id)))
            ->when($this->clientId, fn ($q) => $q->where('client_id', $this->clientId))
            ->groupBy('vat_paid_status')
            ->get();

        $data = [
            [
                'status' => 'Pending VAT',
                'count' => $vatData->where('vat_paid_status', 'pending')->first()->count ?? 0,
                'amount' => $vatData->where('vat_paid_status', 'pending')->first()->amount ?? 0,
            ],
            [
                'status' => 'Paid VAT',
                'count' => $vatData->where('vat_paid_status', 'paid')->first()->count ?? 0,
                'amount' => $vatData->where('vat_paid_status', 'paid')->first()->amount ?? 0,
            ],
            [
                'status' => 'Total VAT Liability',
                'count' => $vatData->sum('count'),
                'amount' => $vatData->sum('amount'),
            ]
        ];

        $pdf = Pdf::loadView('pdf.report', [
            'title' => 'VAT Liability Summary',
            'reportType' => 'vat_summary',
            'data' => $data,
            'date' => now()->format('F d, Y H:i:s'),
            'user' => $user,
        ]);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "report_vat_summary_" . now()->format('YmdHis') . ".pdf"
        );
    }
}
