<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
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

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    protected static function booted()
    {
        static::bootBelongsToCompany();

        static::created(function (Service $service) {
            ActivityLog::log('Created Service', Service::class, $service->id, "Type: {$service->type}, Client ID: {$service->client_id}");
        });

        static::updated(function (Service $service) {
            ActivityLog::log('Updated Service', Service::class, $service->id, "Type: {$service->type}");
        });

        static::deleted(function (Service $service) {
            ActivityLog::log('Deleted Service', Service::class, $service->id, "Type: {$service->type}");
        });
    }
}
