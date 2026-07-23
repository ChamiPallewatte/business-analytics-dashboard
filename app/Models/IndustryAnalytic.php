<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndustryAnalytic extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'industry_type',
        'metric_name',
        'metric_category',
        'metric_value',
        'metric_date',
        'metadata',
    ];

    protected $casts = [
        'metric_value' => 'decimal:2',
        'metric_date' => 'date',
        'metadata' => 'array',
    ];
}
