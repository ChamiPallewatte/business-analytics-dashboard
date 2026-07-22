<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Models\Service;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Pages\Page;

use BackedEnum;

class Reports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Reports & Exports';
    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.reports';

    public string $activeTab = 'client_dues';
    public ?int $clientId = null;

    public function getClients()
    {
        $user = auth()->user();
        return Client::query()
            ->when($user->role === 'staff', fn($q) => $q->where('assigned_manager_id', $user->id))
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    /**
     * Client-wise dues report data.
     */
    public function getClientDuesReport()
    {
        $user = auth()->user();
        return Client::query()
            ->when($user->role === 'staff', fn($q) => $q->where('assigned_manager_id', $user->id))
            ->when($this->clientId, fn($q) => $q->where('id', $this->clientId))
            ->with(['services' => fn($q) => $q->where('service_status', '!=', 'cancelled')])
            ->get()
            ->map(function ($client) {
                $totalServices = $client->services->count();
                $totalValue = $client->services->sum('total_amount');
                $totalPaid = $client->services->sum('paid_amount');
                $balance = $client->services->sum('balance_amount');
                return [
                    'client_name' => $client->name,
                    'company_name' => $client->company_name,
                    'services_count' => $totalServices,
                    'total_value' => $totalValue,
                    'total_paid' => $totalPaid,
                    'balance' => $balance,
                ];
            })
            ->filter(fn($row) => $row['balance'] > 0)
            ->values()
            ->toArray();
    }

    /**
     * Service-wise revenue report data.
     */
    public function getServiceRevenueReport()
    {
        $user = auth()->user();
        return Service::query()
            ->when($user->role === 'staff', fn($q) => $q->whereHas('client', fn($cq) => $cq->where('assigned_manager_id', $user->id)))
            ->when($this->clientId, fn($q) => $q->where('client_id', $this->clientId))
            ->where('service_status', '!=', 'cancelled')
            ->get()
            ->groupBy('type')
            ->map(function ($items, $type) {
                return [
                    'type' => $type,
                    'count' => $items->count(),
                    'revenue' => $items->sum('service_value'),
                    'total_with_vat' => $items->sum('total_amount'),
                    'paid' => $items->sum('paid_amount'),
                    'balance' => $items->sum('balance_amount'),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Monthly renewals report data.
     */
    public function getMonthlyRenewalsReport()
    {
        $user = auth()->user();
        return Service::query()
            ->when($user->role === 'staff', fn($q) => $q->whereHas('client', fn($cq) => $cq->where('assigned_manager_id', $user->id)))
            ->when($this->clientId, fn($q) => $q->where('client_id', $this->clientId))
            ->where('service_status', '!=', 'cancelled')
            ->get()
            ->groupBy(fn($item) => $item->renewal_date->format('F Y'))
            ->map(function ($items, $month) {
                return [
                    'month' => $month,
                    'count' => $items->count(),
                    'total_value' => $items->sum('total_amount'),
                    'renewed_count' => $items->where('payment_status', 'paid')->count(),
                    'pending_count' => $items->where('payment_status', '!=', 'paid')->count(),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * VAT summary report data.
     */
    public function getVatSummaryReport()
    {
        $user = auth()->user();
        $services = Service::query()
            ->when($user->role === 'staff', fn($q) => $q->whereHas('client', fn($cq) => $cq->where('assigned_manager_id', $user->id)))
            ->when($this->clientId, fn($q) => $q->where('client_id', $this->clientId))
            ->where('service_status', '!=', 'cancelled')
            ->get();

        return [
            [
                'status' => 'Pending VAT',
                'count' => $services->where('vat_paid_status', 'pending')->count(),
                'amount' => $services->where('vat_paid_status', 'pending')->sum('vat_amount'),
            ],
            [
                'status' => 'Paid VAT',
                'count' => $services->where('vat_paid_status', 'paid')->count(),
                'amount' => $services->where('vat_paid_status', 'paid')->sum('vat_amount'),
            ],
            [
                'status' => 'Total VAT Liability',
                'count' => $services->count(),
                'amount' => $services->sum('vat_amount'),
            ]
        ];
    }

    /**
     * Pending payments report data.
     */
    public function getPendingPaymentsReport()
    {
        $user = auth()->user();
        return Service::query()
            ->when($user->role === 'staff', fn($q) => $q->whereHas('client', fn($cq) => $cq->where('assigned_manager_id', $user->id)))
            ->when($this->clientId, fn($q) => $q->where('client_id', $this->clientId))
            ->where('balance_amount', '>', 0)
            ->with('client')
            ->orderBy('balance_amount', 'desc')
            ->get()
            ->map(function ($service) {
                return [
                    'client_name' => $service->client->name ?? 'N/A',
                    'company_name' => $service->client->company_name ?? 'N/A',
                    'type' => $service->type,
                    'end_date' => $service->end_date->format('Y-m-d'),
                    'total' => $service->total_amount,
                    'paid' => $service->paid_amount,
                    'balance' => $service->balance_amount,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Export a report to CSV format.
     */
    public function exportCsv(string $reportType)
    {
        $filename = "report_{$reportType}_" . now()->format('YmdHis') . ".csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($reportType) {
            $file = fopen('php://output', 'w');

            switch ($reportType) {
                case 'client_dues':
                    fputcsv($file, ['Client Name', 'Company Name', 'Services Count', 'Total Value ($)', 'Total Paid ($)', 'Balance Due ($)']);
                    foreach ($this->getClientDuesReport() as $row) {
                        fputcsv($file, [$row['client_name'], $row['company_name'], $row['services_count'], $row['total_value'], $row['total_paid'], $row['balance']]);
                    }
                    break;
                case 'service_revenue':
                    fputcsv($file, ['Service Type', 'Active Services', 'Service Value ($)', 'Total with VAT ($)', 'Paid Amount ($)', 'Balance Outstanding ($)']);
                    foreach ($this->getServiceRevenueReport() as $row) {
                        fputcsv($file, [$row['type'], $row['count'], $row['revenue'], $row['total_with_vat'], $row['paid'], $row['balance']]);
                    }
                    break;
                case 'monthly_renewals':
                    fputcsv($file, ['Month', 'Renewing Services', 'Total Renewal Value ($)', 'Fully Paid Renewals', 'Pending Payments']);
                    foreach ($this->getMonthlyRenewalsReport() as $row) {
                        fputcsv($file, [$row['month'], $row['count'], $row['total_value'], $row['renewed_count'], $row['pending_count']]);
                    }
                    break;
                case 'vat_summary':
                    fputcsv($file, ['VAT Payment Status', 'Count of Services', 'VAT Amount ($)']);
                    foreach ($this->getVatSummaryReport() as $row) {
                        fputcsv($file, [$row['status'], $row['count'], $row['amount']]);
                    }
                    break;
                case 'pending_payments':
                    fputcsv($file, ['Client Name', 'Company Name', 'Service Type', 'Expiry Date', 'Total Value ($)', 'Paid Amount ($)', 'Outstanding Balance ($)']);
                    foreach ($this->getPendingPaymentsReport() as $row) {
                        fputcsv($file, [$row['client_name'], $row['company_name'], $row['type'], $row['end_date'], $row['total'], $row['paid'], $row['balance']]);
                    }
                    break;
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export a report to PDF format.
     */
    public function exportPdf(string $reportType)
    {
        $data = [];
        $title = '';

        switch ($reportType) {
            case 'client_dues':
                $data = $this->getClientDuesReport();
                $title = 'Client-wise Outstanding Dues';
                break;
            case 'service_revenue':
                $data = $this->getServiceRevenueReport();
                $title = 'Service-wise Revenue Analysis';
                break;
            case 'monthly_renewals':
                $data = $this->getMonthlyRenewalsReport();
                $title = 'Monthly Renewal Forecast';
                break;
            case 'vat_summary':
                $data = $this->getVatSummaryReport();
                $title = 'VAT Liability Summary';
                break;
            case 'pending_payments':
                $data = $this->getPendingPaymentsReport();
                $title = 'Pending Payments & Overdue Dues';
                break;
        }

        $pdf = Pdf::loadView('pdf.report', [
            'title' => $title,
            'reportType' => $reportType,
            'data' => $data,
            'date' => now()->format('F d, Y H:i:s'),
            'user' => auth()->user(),
        ]);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "report_{$reportType}_" . now()->format('YmdHis') . ".pdf"
        );
    }
}
