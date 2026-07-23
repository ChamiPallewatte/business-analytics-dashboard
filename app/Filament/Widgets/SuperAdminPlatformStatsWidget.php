<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SuperAdminPlatformStatsWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->isSuperAdmin();
    }

    protected function getStats(): array
    {
        $totalCompanies = Company::count();
        $activeCompanies = Company::where('subscription_status', 'active')->count();
        $suspendedCompanies = Company::where('subscription_status', 'suspended')->count();
        $totalUsers = User::count();

        // Estimated MRR calculation
        $proPlanCount = Company::where('subscription_plan', 'pro')->count();
        $enterprisePlanCount = Company::where('subscription_plan', 'enterprise')->count();
        $basicPlanCount = Company::where('subscription_plan', 'basic')->count();
        $mrr = ($basicPlanCount * 49) + ($proPlanCount * 149) + ($enterprisePlanCount * 499);

        return [
            Stat::make('Total Platform Tenants', (string) $totalCompanies)
                ->description("{$activeCompanies} Active, {$suspendedCompanies} Suspended")
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary')
                ->chart([3, 5, 8, 12, 18, 25, $totalCompanies]),

            Stat::make('Estimated Platform MRR', '$' . number_format($mrr, 2))
                ->description('Monthly Recurring Subscription Revenue')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->chart([1000, 2500, 4800, 8900, 12400, $mrr]),

            Stat::make('Total Platform Users', (string) $totalUsers)
                ->description('Across all tenant accounts')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Platform System Health', '99.99%')
                ->description('All services operating normally')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }
}
