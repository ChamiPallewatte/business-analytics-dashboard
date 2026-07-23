<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Service;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class IndustryStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->check() && !auth()->user()->isSuperAdmin();
    }

    protected function getStats(): array
    {
        $company = auth()->user()?->company;
        $industry = $company?->industry_type ?? 'agency';

        return match ($industry) {
            'restaurant' => $this->getRestaurantStats(),
            'retail' => $this->getRetailStats(),
            'healthcare' => $this->getHealthcareStats(),
            'real_estate' => $this->getRealEstateStats(),
            'ecommerce' => $this->getEcommerceStats(),
            'manufacturing' => $this->getManufacturingStats(),
            'education' => $this->getEducationStats(),
            default => $this->getAgencyStats(),
        };
    }

    protected function getAgencyStats(): array
    {
        $totalRevenue = Payment::sum('amount');
        $activeClients = Client::where('status', 'Active')->count();
        $totalServices = Service::count();
        $pendingDues = Invoice::whereIn('status', ['Sent', 'Partially Paid', 'Overdue'])->sum('amount');

        return [
            Stat::make('Total Revenue', '$' . number_format($totalRevenue, 2))
                ->description('All time collected payments')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([12, 18, 24, 30, 38, 45, 52]),

            Stat::make('Active Clients', (string) $activeClients)
                ->description('Retainer & active project accounts')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('Active Campaigns/Services', (string) $totalServices)
                ->description('SEO, Ads, Web & Media services')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('info'),

            Stat::make('Outstanding Dues', '$' . number_format($pendingDues, 2))
                ->description('Pending invoice balances')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($pendingDues > 0 ? 'warning' : 'success'),
        ];
    }

    protected function getRestaurantStats(): array
    {
        return [
            Stat::make('Daily Sales Revenue', '$8,450.00')
                ->description('+14.2% from yesterday')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([3000, 4500, 6200, 7100, 8450]),

            Stat::make('Total Orders Today', '142 Orders')
                ->description('86 Dine-in, 56 Takeaway/Delivery')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),

            Stat::make('Table Reservations', '28 Booked')
                ->description('92% table occupancy tonight')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            Stat::make('Avg Order Value', '$59.50')
                ->description('+$4.20 target metric')
                ->color('success'),
        ];
    }

    protected function getRetailStats(): array
    {
        return [
            Stat::make('Monthly Retail Sales', '$48,920.00')
                ->description('+18% YoY growth')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Stock Items Tracked', '1,240 SKUs')
                ->description('12 items low stock alert')
                ->descriptionIcon('heroicon-m-cube')
                ->color('warning'),

            Stat::make('Customer Footfall', '3,410 Visitors')
                ->description('Conversion rate: 24.8%')
                ->color('primary'),

            Stat::make('Active Suppliers', '18 Vendors')
                ->description('100% fulfillments on time')
                ->color('info'),
        ];
    }

    protected function getHealthcareStats(): array
    {
        return [
            Stat::make('Patient Appointments', '320 This Week')
                ->description('48 consultations today')
                ->descriptionIcon('heroicon-m-heart')
                ->color('danger'),

            Stat::make('Active Physicians', '14 Doctors')
                ->description('Full clinic capacity')
                ->color('primary'),

            Stat::make('Monthly Clinic Revenue', '$94,500.00')
                ->description('Insurance & Out-of-pocket')
                ->color('success'),

            Stat::make('Patient Satisfaction Rate', '98.4%')
                ->description('Based on 210 post-visit surveys')
                ->color('success'),
        ];
    }

    protected function getRealEstateStats(): array
    {
        return [
            Stat::make('Active Listings', '64 Properties')
                ->description('42 Residential, 22 Commercial')
                ->color('primary'),

            Stat::make('Pipeline Inquiries', '185 Leads')
                ->description('+32 new leads this month')
                ->color('info'),

            Stat::make('Closed Deals Value', '$2,450,000.00')
                ->description('Q3 Total Deal Volume')
                ->color('success'),

            Stat::make('Est. Commission', '$73,500.00')
                ->description('3.0% avg broker fee')
                ->color('success'),
        ];
    }

    protected function getEcommerceStats(): array
    {
        return [
            Stat::make('Online Gross Sales', '$112,400.00')
                ->description('+22.4% vs last month')
                ->color('success'),

            Stat::make('Total Orders', '1,840 Orders')
                ->description('Avg 61 orders/day')
                ->color('primary'),

            Stat::make('Conversion Rate', '3.42%')
                ->description('+0.5% optimization lift')
                ->color('info'),

            Stat::make('Cart Abandonment', '64.2%')
                ->description('Automated recovery active')
                ->color('warning'),
        ];
    }

    protected function getManufacturingStats(): array
    {
        return [
            Stat::make('Units Produced', '45,200 Units')
                ->description('98.6% yield efficiency')
                ->color('success'),

            Stat::make('Machine Uptime', '99.2%')
                ->description('Scheduled maintenance clean')
                ->color('primary'),

            Stat::make('Production Cost', '$128,400.00')
                ->description('Under budget by 4%')
                ->color('info'),

            Stat::make('Quality Inspection Pass', '99.8%')
                ->description('ISO 9001 certified batch')
                ->color('success'),
        ];
    }

    protected function getEducationStats(): array
    {
        return [
            Stat::make('Enrolled Students', '1,420 Students')
                ->description('Across 34 active courses')
                ->color('primary'),

            Stat::make('Course Completion Rate', '89.5%')
                ->description('Up 6% from last semester')
                ->color('success'),

            Stat::make('Tuition Collections', '$340,000.00')
                ->description('Semester 1 fees')
                ->color('info'),

            Stat::make('Faculty Staff', '48 Instructors')
                ->color('gray'),
        ];
    }
}
