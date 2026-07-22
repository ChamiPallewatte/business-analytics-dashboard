<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class ClientDuesTable extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public ?int $clientId = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Client::query()
                    ->whereHas('services', fn ($q) => $q->where('balance_amount', '>', 0))
                    ->when(auth()->user()->role === 'staff', fn ($q) => $q->where('assigned_manager_id', auth()->id()))
                    ->when($this->clientId, fn ($q) => $q->where('id', $this->clientId))
                    ->withSum('services as total_value', 'total_amount')
                    ->withSum('services as total_paid', 'paid_amount')
                    ->withSum('services as total_balance', 'balance_amount')
            )
            ->heading('Client-wise Outstanding Dues')
            ->description('Detailed outstanding balances by client.')
            ->columns([
                TextColumn::make('name')
                    ->label('Client Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable(),

                TextColumn::make('services_count')
                    ->counts('services')
                    ->label('Active Services')
                    ->alignCenter(),

                TextColumn::make('total_value')
                    ->label('Total Value')
                    ->money('AED'),

                TextColumn::make('total_paid')
                    ->label('Total Paid')
                    ->money('AED')
                    ->color('success'),

                TextColumn::make('total_balance')
                    ->label('Balance Due')
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
        $filename = "report_client_dues_" . now()->format('YmdHis') . ".csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Client Name', 'Company Name', 'Services Count', 'Total Value ($)', 'Total Paid ($)', 'Balance Due ($)']);

            $user = auth()->user();
            $clients = Client::query()
                ->whereHas('services', fn ($q) => $q->where('balance_amount', '>', 0))
                ->when($user->role === 'staff', fn($q) => $q->where('assigned_manager_id', $user->id))
                ->when($this->clientId, fn ($q) => $q->where('id', $this->clientId))
                ->withSum('services as total_value', 'total_amount')
                ->withSum('services as total_paid', 'paid_amount')
                ->withSum('services as total_balance', 'balance_amount')
                ->get();

            foreach ($clients as $client) {
                fputcsv($file, [
                    $client->name,
                    $client->company_name,
                    $client->services()->count(),
                    $client->total_value ?? 0,
                    $client->total_paid ?? 0,
                    $client->total_balance ?? 0
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
        $clients = Client::query()
            ->whereHas('services', fn ($q) => $q->where('balance_amount', '>', 0))
            ->when($user->role === 'staff', fn($q) => $q->where('assigned_manager_id', $user->id))
            ->when($this->clientId, fn ($q) => $q->where('id', $this->clientId))
            ->withSum('services as total_value', 'total_amount')
            ->withSum('services as total_paid', 'paid_amount')
            ->withSum('services as total_balance', 'balance_amount')
            ->get()
            ->map(fn($c) => [
                'client_name' => $c->name,
                'company_name' => $c->company_name,
                'services_count' => $c->services()->count(),
                'total_value' => $c->total_value ?? 0,
                'total_paid' => $c->total_paid ?? 0,
                'balance' => $c->total_balance ?? 0,
            ])
            ->toArray();

        $pdf = Pdf::loadView('pdf.report', [
            'title' => 'Client-wise Outstanding Dues',
            'reportType' => 'client_dues',
            'data' => $clients,
            'date' => now()->format('F d, Y H:i:s'),
            'user' => $user,
        ]);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "report_client_dues_" . now()->format('YmdHis') . ".pdf"
        );
    }
}
