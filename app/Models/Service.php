<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'type',
        'start_date',
        'end_date',
        'renewal_date',
        'service_value',
        'vat_percent',
        'vat_amount',
        'total_amount',
        'paid_amount',
        'balance_amount',
        'payment_status',
        'vat_paid_status',
        'service_status',
        'billing_cycle',
        'package_plan',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'renewal_date' => 'date',
        'service_value' => 'decimal:2',
        'vat_percent' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
    ];

    // Allowed Service Types
    public static array $types = [
        'Website Development' => 'Website Development',
        'SEO' => 'SEO',
        'Google Ads' => 'Google Ads',
        'Social Media' => 'Social Media',
        'Hosting' => 'Hosting',
        'Domain' => 'Domain',
        'Maintenance' => 'Maintenance',
        'E-commerce' => 'E-commerce',
        'Other' => 'Other',
    ];

    /**
     * Get the client associated with this service.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the payment logs for this service.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the invoices associated with this service.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the expenses associated with this service.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Auto-calculate VAT, Total, Balance, and Payment Status on save.
     */
    protected static function booted()
    {
        static::created(function (Service $service) {
            ActivityLog::log('Created Service', Service::class, $service->id, "Type: {$service->type}, Client ID: {$service->client_id}");
        });

        static::updated(function (Service $service) {
            ActivityLog::log('Updated Service', Service::class, $service->id, "Type: {$service->type}");
        });

        static::deleted(function (Service $service) {
            ActivityLog::log('Deleted Service', Service::class, $service->id, "Type: {$service->type}");
        });
        static::saving(function (Service $service) {
            // Recalculate VAT Amount
            $service->vat_amount = round($service->service_value * ($service->vat_percent / 100), 2);
            
            // Recalculate Total
            $service->total_amount = $service->service_value + $service->vat_amount;
            
            // Recalculate Balance
            $service->balance_amount = max(0, $service->total_amount - $service->paid_amount);

            // Auto-update Payment Status
            if ($service->balance_amount <= 0) {
                $service->payment_status = 'paid';
            } elseif ($service->paid_amount > 0) {
                $service->payment_status = 'partially_paid';
            } else {
                $service->payment_status = 'unpaid';
            }
        });
    }
}
