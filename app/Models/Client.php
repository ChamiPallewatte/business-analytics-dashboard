<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'company_name',
        'business_type',
        'industry',
        'contact_person',
        'contact_designation',
        'phone_code',
        'mobile',
        'alternate_phone_code',
        'alternate_phone_number',
        'whatsapp_phone_code',
        'whatsapp_phone_number',
        'email',
        'trn',
        'address',
        'country',
        'emirate',
        'city',
        'postal_code',
        'website',
        'assigned_manager_id',
        'status',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_manager_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ClientDocument::class);
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

        static::created(function (Client $client) {
            ActivityLog::log('Created Client', Client::class, $client->id, "Name: {$client->name}, Company: {$client->company_name}");
        });

        static::updated(function (Client $client) {
            ActivityLog::log('Updated Client', Client::class, $client->id, "Name: {$client->name}");
        });

        static::deleted(function (Client $client) {
            ActivityLog::log('Deleted Client', Client::class, $client->id, "Name: {$client->name}");
        });
    }
}
