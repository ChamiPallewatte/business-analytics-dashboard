<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Models\Invoice;
use App\Models\Expense;
use App\Models\Setting;
use Carbon\Carbon;

class SalesExpensesChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Sales vs Expenses Overview';
    protected static ?int $sort = 2; // Show second

    protected function getData(): array
    {
        $user = auth()->user();
        $currency = Setting::getCurrency();

        // Get filter dates
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? null;

        $start = $startDate ? Carbon::parse($startDate) : now()->startOfMonth();
        $end = $endDate ? Carbon::parse($endDate) : now()->endOfMonth();

        // Query sales (invoices)
        $invoiceQuery = Invoice::query()
            ->whereBetween('invoice_date', [$start, $end])
            ->where('status', '!=', 'Cancelled');

        if ($user && $user->role === 'staff') {
            $invoiceQuery->whereHas('client', fn ($q) => $q->where('assigned_manager_id', $user->id));
        }

        $totalSales = floatval($invoiceQuery->sum('amount'));

        // Query expenses
        $expenseQuery = Expense::query()
            ->whereBetween('expense_date', [$start, $end]);

        if ($user && $user->role === 'staff') {
            $expenseQuery->whereHas('client', fn ($q) => $q->where('assigned_manager_id', $user->id));
        }

        $totalExpenses = floatval($expenseQuery->sum('amount'));

        return [
            'datasets' => [
                [
                    'label' => 'Amount (' . $currency . ')',
                    'data' => [$totalSales, $totalExpenses],
                    'backgroundColor' => ['#2563eb', '#ef4444'], // Blue for sales, Red for expenses
                    'borderColor' => ['#1d4ed8', '#dc2626'],
                    'borderWidth' => 1,
                ],
            ],
            'labels' => ['Total Sales (Excl. VAT)', 'Total Expenses'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
