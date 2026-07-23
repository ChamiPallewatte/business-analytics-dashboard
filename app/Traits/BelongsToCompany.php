<?php

namespace App\Traits;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        // Global scope to isolate data per company tenant
        static::addGlobalScope('company_tenant', function (Builder $builder) {
            if (auth()->check()) {
                $user = auth()->user();
                if (!$user->isSuperAdmin() && $user->company_id) {
                    $builder->where($builder->getModel()->getTable() . '.company_id', $user->company_id);
                }
            }
        });

        // Automatically set company_id on model creation
        static::creating(function ($model) {
            if (empty($model->company_id) && auth()->check() && auth()->user()->company_id) {
                $model->company_id = auth()->user()->company_id;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
