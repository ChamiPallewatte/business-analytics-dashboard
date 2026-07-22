<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;

    protected array $serviceData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Extract service data to avoid saving to client model directly
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'service_')) {
                $this->serviceData[$key] = $value;
                unset($data[$key]);
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $client = $this->record;

        if (!empty($this->serviceData['service_type'])) {
            $serviceType = $this->serviceData['service_type'];
            $packagePlan = $this->serviceData['service_package_plan'] ?? 'Custom';
            
            $prices = [
                'Website Development' => ['Gold' => 8000, 'Silver' => 4000, 'Bronze' => 2000, 'Custom' => 3000],
                'SEO' => ['Gold' => 4000, 'Silver' => 2000, 'Bronze' => 1000, 'Custom' => 1500],
                'Google Ads' => ['Gold' => 5000, 'Silver' => 3000, 'Bronze' => 1500, 'Custom' => 2000],
                'Social Media' => ['Gold' => 6000, 'Silver' => 4000, 'Bronze' => 2000, 'Custom' => 2500],
                'Hosting' => ['Gold' => 1500, 'Silver' => 800, 'Bronze' => 400, 'Custom' => 600],
                'Domain' => ['Gold' => 300, 'Silver' => 150, 'Bronze' => 80, 'Custom' => 100],
            ];

            $serviceValue = $prices[$serviceType][$packagePlan] ?? 2000;
            
            $vatPercent = \App\Models\Setting::getCountryVatPercent($client->country);
            $vatAmount = round($serviceValue * ($vatPercent / 100), 2);
            $totalAmount = $serviceValue + $vatAmount;

            $client->services()->create([
                'type' => $serviceType,
                'start_date' => $this->serviceData['service_start_date'] ?? now(),
                'end_date' => $this->serviceData['service_end_date'] ?? now()->addYear(),
                'renewal_date' => $this->serviceData['service_renewal_date'] ?? ($this->serviceData['service_end_date'] ?? now()->addYear()),
                'service_value' => $serviceValue,
                'vat_percent' => $vatPercent,
                'vat_amount' => $vatAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => 0.00,
                'balance_amount' => $totalAmount,
                'payment_status' => 'unpaid',
                'vat_paid_status' => 'pending',
                'service_status' => 'active',
                'billing_cycle' => $this->serviceData['service_billing_cycle'] ?? 'Monthly',
                'package_plan' => $packagePlan,
                'description' => $this->serviceData['service_description'] ?? null,
            ]);
        }
    }
}
