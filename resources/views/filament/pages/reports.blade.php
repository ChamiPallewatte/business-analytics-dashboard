<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Page-level client filter --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-4 bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-900 dark:border-gray-800 gap-4">
            <div class="space-y-1">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Filter Report Data</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Select a specific client to filter all tab results and exports.</p>
            </div>
            <div class="w-full sm:w-72">
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="clientId">
                        <option value="">All Clients</option>
                        @foreach($this->getClients() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </div>

        {{-- Filament Native Tabs --}}
        <x-filament::tabs>
            <x-filament::tabs.item
                :active="$activeTab === 'client_dues'"
                wire:click="$set('activeTab', 'client_dues')"
                icon="heroicon-o-users"
            >
                Client Dues
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$activeTab === 'service_revenue'"
                wire:click="$set('activeTab', 'service_revenue')"
                icon="heroicon-o-chart-bar"
            >
                Service Revenue
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$activeTab === 'monthly_renewals'"
                wire:click="$set('activeTab', 'monthly_renewals')"
                icon="heroicon-o-calendar"
            >
                Monthly Renewals
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$activeTab === 'vat_summary'"
                wire:click="$set('activeTab', 'vat_summary')"
                icon="heroicon-o-calculator"
            >
                VAT Summary
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$activeTab === 'pending_payments'"
                wire:click="$set('activeTab', 'pending_payments')"
                icon="heroicon-o-credit-card"
            >
                Pending Payments
            </x-filament::tabs.item>
        </x-filament::tabs>

        {{-- Dynamic Native Table Widget Rendering --}}
        <div class="mt-4">
            @if ($activeTab === 'client_dues')
                @livewire(\App\Filament\Widgets\ClientDuesTable::class, ['clientId' => $clientId], key('client-dues-' . $clientId))
            @elseif ($activeTab === 'service_revenue')
                @livewire(\App\Filament\Widgets\ServiceRevenueTable::class, ['clientId' => $clientId], key('service-revenue-' . $clientId))
            @elseif ($activeTab === 'monthly_renewals')
                @livewire(\App\Filament\Widgets\MonthlyRenewalsTable::class, ['clientId' => $clientId], key('monthly-renewals-' . $clientId))
            @elseif ($activeTab === 'vat_summary')
                @livewire(\App\Filament\Widgets\VatSummaryTable::class, ['clientId' => $clientId], key('vat-summary-' . $clientId))
            @elseif ($activeTab === 'pending_payments')
                @livewire(\App\Filament\Widgets\PendingPaymentsTable::class, ['clientId' => $clientId], key('pending-payments-' . $clientId))
            @endif
        </div>
    </div>
</x-filament-panels::page>
