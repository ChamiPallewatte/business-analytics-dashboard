<?php

namespace App\Models;

use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model implements HasName
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'industry_type',
        'logo_path',
        'primary_color',
        'secondary_color',
        'subscription_plan',
        'subscription_status',
        'max_users',
        'storage_limit_mb',
        'timezone',
        'currency',
        'custom_settings',
    ];

    protected $casts = [
        'custom_settings' => 'array',
        'max_users' => 'integer',
        'storage_limit_mb' => 'integer',
    ];

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function industryAnalytics(): HasMany
    {
        return $this->hasMany(IndustryAnalytic::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function isSuspended(): bool
    {
        return $this->subscription_status === 'suspended';
    }

    public function getIndustryLabelAttribute(): string
    {
        return match ($this->industry_type) {
            'agency' => 'Digital Marketing Agency',
            'restaurant' => 'Restaurant & Hospitality',
            'retail' => 'Retail Store',
            'healthcare' => 'Healthcare Clinic',
            'real_estate' => 'Real Estate',
            'ecommerce' => 'E-Commerce Business',
            'manufacturing' => 'Manufacturing',
            'education' => 'Educational Institution',
            default => 'General Business',
        };
    }
}
