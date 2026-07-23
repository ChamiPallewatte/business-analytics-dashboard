<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'key',
        'value',
    ];

    public static function get(string $key, $default = null)
    {
        $companyId = auth()->check() ? auth()->user()->company_id : null;
        $query = static::where('key', $key);
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        $setting = $query->first();
        return $setting ? $setting->value : $default;
    }

    public static function set(string $key, $value)
    {
        $companyId = auth()->check() ? auth()->user()->company_id : null;
        return static::updateOrCreate(
            ['company_id' => $companyId, 'key' => $key],
            ['value' => $value]
        );
    }

    public static function getCurrency(): string
    {
        if (auth()->check() && auth()->user()->company) {
            return auth()->user()->company->currency ?? 'USD';
        }
        $country = static::get('default_country', 'United Arab Emirates');
        return static::getCountryCurrency($country);
    }

    public static function getCountryCurrency(?string $country): string
    {
        $map = [
            'United Arab Emirates' => 'AED',
            'Saudi Arabia' => 'SAR',
            'Oman' => 'OMR',
            'Bahrain' => 'BHD',
            'Qatar' => 'QAR',
            'Kuwait' => 'KWD',
            'United States' => 'USD',
            'United Kingdom' => 'GBP',
        ];
        return $map[$country] ?? 'USD';
    }

    public static function getVatPercent(): float
    {
        $country = static::get('default_country', 'United Arab Emirates');
        return static::getCountryVatPercent($country);
    }

    public static function getCountryVatPercent(?string $country): float
    {
        $map = [
            'United Arab Emirates' => 5.00,
            'Saudi Arabia' => 15.00,
            'Oman' => 5.00,
            'Bahrain' => 10.00,
            'Qatar' => 0.00,
            'Kuwait' => 0.00,
            'United States' => 0.00,
            'United Kingdom' => 20.00,
        ];
        return $map[$country] ?? 5.00;
    }
}
