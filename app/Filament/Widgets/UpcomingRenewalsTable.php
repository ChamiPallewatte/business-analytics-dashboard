<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Services\ServiceResource;
use App\Models\Service;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class UpcomingRenewalsTable extends TableWidget
{
    protected static ?int $sort = 2; // Show second

    protected int|string|array $columnSpan = 'full'; // Span the full width of the dashboard

    public ?int $clientId = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Service::query()
                    ->whereBetween('renewal_date', [now(), now()->addDays(30)])
                    ->when(auth()->user()->role === 'staff', function ($q) {
                        $q->whereHas('client', function ($clientQ) {
                            $clientQ->where('assigned_manager_id', auth()->id());
                        });
                    })
                    ->when($this->clientId, fn ($q) => $q->where('client_id', $this->clientId))
                    ->orderBy('renewal_date', 'asc')
            )
            ->heading('Upcoming Renewals (Next 30 Days)')
            ->columns([
                TextColumn::make('client.name')
                    ->label('Client Name')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Service Type'),

                TextColumn::make('renewal_date')
                    ->label('Renewal Date')
                    ->date()
                    ->color('warning')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total (incl. VAT)')
                    ->money('AED'),

                TextColumn::make('service_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'completed' => 'success',
                        'expired' => 'danger',
                        'suspended' => 'warning',
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in_progress' => 'In Progress',
                        default => ucfirst($state),
                    }),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('View / Edit')
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn (Service $record): string => ServiceResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
