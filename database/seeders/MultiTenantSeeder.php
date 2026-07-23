<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Company;
use App\Models\Expense;
use App\Models\IndustryAnalytic;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Report;
use App\Models\Service;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MultiTenantSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Platform Super Admin
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@platform.com'],
            [
                'name' => 'Global Super Admin',
                'password' => Hash::make('Password123!'),
                'role' => 'super_admin',
                'company_id' => null,
                'status' => 'active',
                'department' => 'Executive Office',
                'position' => 'Chief Executive Officer',
            ]
        );

        // 2. Create Multi-Industry Companies
        $companiesData = [
            [
                'name' => 'Apex Digital Marketing',
                'slug' => 'apex-digital-marketing',
                'industry_type' => 'agency',
                'primary_color' => '#2563eb',
                'subscription_plan' => 'enterprise',
                'subscription_status' => 'active',
                'currency' => 'USD',
                'admin_email' => 'agency@apexmarketing.com',
                'admin_name' => 'Marcus Vance',
            ],
            [
                'name' => 'Bella Italia Ristorante',
                'slug' => 'bella-italia-ristorante',
                'industry_type' => 'restaurant',
                'primary_color' => '#dc2626',
                'subscription_plan' => 'pro',
                'subscription_status' => 'active',
                'currency' => 'EUR',
                'admin_email' => 'admin@bellaitalia.com',
                'admin_name' => 'Giovanni Rossi',
            ],
            [
                'name' => 'Urban Trendz Fashion',
                'slug' => 'urban-trendz-fashion',
                'industry_type' => 'retail',
                'primary_color' => '#7c3aed',
                'subscription_plan' => 'pro',
                'subscription_status' => 'active',
                'currency' => 'USD',
                'admin_email' => 'admin@urbantrendz.com',
                'admin_name' => 'Elena Rostova',
            ],
            [
                'name' => 'MediCare Health Clinic',
                'slug' => 'medicare-health-clinic',
                'industry_type' => 'healthcare',
                'primary_color' => '#059669',
                'subscription_plan' => 'enterprise',
                'subscription_status' => 'active',
                'currency' => 'USD',
                'admin_email' => 'admin@medicare.com',
                'admin_name' => 'Dr. Sarah Jenkins',
            ],
            [
                'name' => 'Horizon Real Estate',
                'slug' => 'horizon-real-estate',
                'industry_type' => 'real_estate',
                'primary_color' => '#d97706',
                'subscription_plan' => 'basic',
                'subscription_status' => 'active',
                'currency' => 'USD',
                'admin_email' => 'admin@horizonrealty.com',
                'admin_name' => 'David Sterling',
            ],
        ];

        foreach ($companiesData as $cData) {
            $company = Company::updateOrCreate(
                ['slug' => $cData['slug']],
                [
                    'name' => $cData['name'],
                    'industry_type' => $cData['industry_type'],
                    'primary_color' => $cData['primary_color'],
                    'subscription_plan' => $cData['subscription_plan'],
                    'subscription_status' => $cData['subscription_status'],
                    'currency' => $cData['currency'],
                    'max_users' => 20,
                    'storage_limit_mb' => 2000,
                ]
            );

            // Create Company Admin
            $companyAdmin = User::updateOrCreate(
                ['email' => $cData['admin_email']],
                [
                    'name' => $cData['admin_name'],
                    'password' => Hash::make('Password123!'),
                    'role' => 'company_admin',
                    'company_id' => $company->id,
                    'status' => 'active',
                    'department' => 'Management',
                    'position' => 'Company Director',
                ]
            );

            // Create Company Staff / Employee
            $employee = User::updateOrCreate(
                ['email' => "staff.{$company->slug}@platform.com"],
                [
                    'name' => "Staff Member ({$company->name})",
                    'password' => Hash::make('Password123!'),
                    'role' => 'employee',
                    'company_id' => $company->id,
                    'status' => 'active',
                    'department' => 'Operations',
                    'position' => 'Specialist',
                ]
            );

            // Create Team
            $team = Team::updateOrCreate(
                ['company_id' => $company->id, 'name' => 'Core Operations Team'],
                [
                    'description' => 'Primary team handling client projects and analytics',
                    'team_lead_id' => $companyAdmin->id,
                ]
            );
            $team->members()->sync([$companyAdmin->id, $employee->id]);

            // Create Sample Client & Services for Company
            $client = Client::create([
                'company_id' => $company->id,
                'name' => "Acme {$company->industry_label} Account",
                'company_name' => "Acme Global ({$cData['name']})",
                'contact_person' => $cData['admin_name'],
                'business_type' => $cData['industry_type'],
                'industry' => $company->industry_label,
                'email' => "client@acme-{$company->slug}.com",
                'mobile' => '+15551234567',
                'status' => 'Active',
                'assigned_manager_id' => $companyAdmin->id,
            ]);

            $service = Service::create([
                'company_id' => $company->id,
                'client_id' => $client->id,
                'type' => 'Website Development',
                'start_date' => now()->subMonth(),
                'end_date' => now()->addYear(),
                'renewal_date' => now()->addYear(),
                'service_value' => 5000.00,
                'vat_percent' => 5.00,
                'vat_amount' => 250.00,
                'total_amount' => 5250.00,
                'paid_amount' => 5250.00,
                'balance_amount' => 0.00,
                'payment_status' => '100% Paid',
                'service_status' => 'Active',
            ]);

            $invoice = Invoice::create([
                'company_id' => $company->id,
                'client_id' => $client->id,
                'service_id' => $service->id,
                'amount' => 5000.00,
                'vat_percent' => 5.00,
                'vat_amount' => 250.00,
                'total_amount' => 5250.00,
                'status' => 'Paid',
                'invoice_date' => now()->subDays(10),
                'due_date' => now()->addDays(20),
            ]);

            Payment::create([
                'company_id' => $company->id,
                'service_id' => $service->id,
                'invoice_id' => $invoice->id,
                'amount' => 5250.00,
                'payment_method' => 'Bank Transfer',
                'payment_date' => now()->subDays(5),
                'status' => '100% Paid',
            ]);

            Expense::create([
                'company_id' => $company->id,
                'client_id' => $client->id,
                'type' => 'Software Subscription',
                'vendor_name' => 'Cloud Host Pro',
                'expense_date' => now()->subDays(3),
                'amount' => 450.00,
                'vat_amount' => 0.00,
                'payment_status' => 'Paid',
            ]);

            // Seed Sample Report
            Report::create([
                'company_id' => $company->id,
                'user_id' => $companyAdmin->id,
                'title' => "Monthly {$company->industry_label} Analytics Summary",
                'type' => 'general',
                'industry_type' => $company->industry_type,
                'format' => 'pdf',
                'status' => 'completed',
            ]);

            // Seed Industry Analytics
            IndustryAnalytic::create([
                'company_id' => $company->id,
                'industry_type' => $company->industry_type,
                'metric_name' => 'Monthly Gross Performance',
                'metric_category' => 'Revenue',
                'metric_value' => rand(15000, 95000),
                'metric_date' => now()->toDateString(),
            ]);

            // Log activity
            ActivityLog::create([
                'company_id' => $company->id,
                'user_id' => $companyAdmin->id,
                'action' => 'Seeded Workspace',
                'details' => "Company workspace {$company->name} initialized successfully.",
            ]);
        }
    }
}
