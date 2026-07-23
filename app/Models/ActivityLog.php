<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'user_id',
        'action',
        'model_type',
        'model_id',
        'details',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $action, ?string $modelType = null, ?int $modelId = null, ?string $details = null)
    {
        $companyId = auth()->check() ? auth()->user()->company_id : null;

        return static::create([
            'company_id' => $companyId,
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'details' => $details,
        ]);
    }
}
