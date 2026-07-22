<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\Service;
use App\Models\Invoice;
use App\Models\Expense;
use App\Models\Setting;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Carbon\Carbon;

class StatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1; // Show first

    protected function getStats(): array
    {
        $user = auth()->user();
        $currency = Setting::getCurrency();

        // Parse date filters
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? null;
        
        $start = $startDate ? Carbon::parse($startDate) : now()->startOfMonth();
        $end = $endDate ? Carbon::parse($endDate) : now()->endOfMonth();

        // 1. Client metrics
        $clientQuery = Client::query();
        if ($user && $user->role === 'staff') {
            $clientQuery->where('assigned_manager_id', $user->id);
        }

        $totalClients = (clone $clientQuery)->count();
        $activeClients = (clone $clientQuery)->where('status', 'active')->count();
        $newClientsThisPeriod = (clone $clientQuery)->whereBetween('created_at', [$start, $end])->count();
        $activePercentage = $totalClients > 0 ? round(($activeClients / $totalClients) * 100) : 0;

        // 2. Upcoming Renewals
        $serviceQuery = Service::query();
        if ($user && $user->role === 'staff') {
            $serviceQuery->whereHas('client', function ($q) use ($user) {
                $q->where('assigned_manager_id', $user->id);
            });
        }
        $upcomingRenewals = (clone $serviceQuery)
            ->whereBetween('renewal_date', [now(), now()->addDays(30)])
            ->count();

        // 3. Invoices Query (Sales)
        $invoiceQuery = Invoice::query();
        if ($user && $user->role === 'staff') {
            $invoiceQuery->whereHas('client', function ($q) use ($user) {
                $q->where('assigned_manager_id', $user->id);
            });
        }
        $periodInvoices = (clone $invoiceQuery)
            ->whereBetween('invoice_date', [$start, $end])
            ->where('status', '!=', 'Cancelled')
            ->get();

        $totalSales = $periodInvoices->sum('amount');
        $vatDue = $periodInvoices->sum('vat_amount');

        // 4. Expenses Query
        $expenseQuery = Expense::query();
        if ($user && $user->role === 'staff') {
            $expenseQuery->whereHas('client', function ($q) use ($user) {
                $q->where('assigned_manager_id', $user->id);
            });
        }
        $totalExpenses = (clone $expenseQuery)
            ->whereBetween('expense_date', [$start, $end])
            ->sum('amount');

        // 5. Margin Calculations
        $totalMargin = $totalSales - $totalExpenses;
        $marginPercentage = $totalSales > 0 ? round(($totalMargin / $totalSales) * 100) : 0;

        // 6. Outstanding/Total Due in Period
        $totalDue = 0;
        foreach ($periodInvoices as $inv) {
            if ($inv->status !== 'Paid') {
                $paidForInvoice = $inv->payments()->sum('amount');
                $totalDue += max(0, $inv->total_amount - $paidForInvoice);
            }
        }

        $netReceivables = $totalDue + $vatDue;

        // Generate trend comparisons for tooltips (comparing with previous period of same length)
        $diffInDays = $start->diffInDays($end) + 1;
        $prevStart = (clone $start)->subDays($diffInDays);
        $prevEnd = (clone $start)->subDays(1);

        $prevPeriodInvoices = (clone $invoiceQuery)
            ->whereBetween('invoice_date', [$prevStart, $prevEnd])
            ->where('status', '!=', 'Cancelled')
            ->get();
        $prevSales = $prevPeriodInvoices->sum('amount');

        $prevExpenses = (clone $expenseQuery)
            ->whereBetween('expense_date', [$prevStart, $prevEnd])
            ->sum('amount');

        $salesChangePct = $prevSales > 0 ? round((($totalSales - $prevSales) / $prevSales) * 100, 1) : 0;
        $expensesChangePct = $prevExpenses > 0 ? round((($totalExpenses - $prevExpenses) / $prevExpenses) * 100, 1) : 0;

        return [
            Stat::make('Total Clients', $totalClients)
                ->description("+{$newClientsThisPeriod} new this period")
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Active Clients', $activeClients)
                ->description("{$activePercentage}% of total clients")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Upcoming Renewals', $upcomingRenewals)
                ->description('Services renewing soon')
                ->descriptionIcon('heroicon-m-clock')
                ->color($upcomingRenewals > 0 ? 'warning' : 'success'),

            Stat::make('Total Sales (Excl. VAT)', $currency . ' ' . number_format($totalSales, 2))
                ->description(($salesChangePct >= 0 ? '+' : '') . $salesChangePct . '% vs last period')
                ->descriptionIcon($salesChangePct >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($totalSales >= $prevSales ? 'success' : 'danger'),

            Stat::make('Total Expenses', $currency . ' ' . number_format($totalExpenses, 2))
                ->description(($expensesChangePct >= 0 ? '+' : '') . $expensesChangePct . '% vs last period')
                ->descriptionIcon($expensesChangePct >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($totalExpenses <= $prevExpenses ? 'success' : 'danger'),

            Stat::make('Total Margin', $currency . ' ' . number_format($totalMargin, 2))
                ->description("{$marginPercentage}% margin")
                ->descriptionIcon('heroicon-m-presentation-chart-line')
                ->color($totalMargin >= 0 ? 'success' : 'danger'),

            Stat::make('Total Due (Outstanding)', $currency . ' ' . number_format($totalDue, 2))
                ->description('Unpaid balances in period')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($totalDue > 0 ? 'danger' : 'success'),

            Stat::make('VAT Due', $currency . ' ' . number_format($vatDue, 2))
                ->description('VAT collected on sales')
                ->descriptionIcon('heroicon-m-calculator')
                ->color($vatDue > 0 ? 'warning' : 'success'),

            Stat::make('Net Receivables (Incl. VAT)', $currency . ' ' . number_format($netReceivables, 2))
                ->description('Total due including VAT')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($netReceivables > 0 ? 'info' : 'success'),
        ];
    }
}
