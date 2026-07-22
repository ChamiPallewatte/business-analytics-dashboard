<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get value for setting key.
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set value for setting key.
     */
    public static function set(string $key, $value)
    {
        return static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Get currency code based on default country.
     */
    public static function getCurrency(): string
    {
        $country = static::get('default_country', 'United Arab Emirates');
        return static::getCountryCurrency($country);
    }

    /**
     * Get currency for country.
     */
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
        return $map[$country] ?? 'AED';
    }

    /**
     * Get standard VAT percent based on default country.
     */
    public static function getVatPercent(): float
    {
        $country = static::get('default_country', 'United Arab Emirates');
        return static::getCountryVatPercent($country);
    }

    /**
     * Get standard VAT percent for country.
     */
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
