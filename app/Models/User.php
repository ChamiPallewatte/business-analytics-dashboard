<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'role', // super_admin, company_admin, employee
        'department',
        'position',
        'status',
        'custom_permissions',
        'two_factor_enabled',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'custom_permissions' => 'array',
            'two_factor_enabled' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user')->withTimestamps();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isCompanyAdmin(): bool
    {
        return $this->role === 'company_admin' || $this->role === 'admin';
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee' || $this->role === 'staff';
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin() || $this->isCompanyAdmin()) {
            return true;
        }

        if (is_array($this->custom_permissions)) {
            return in_array($permission, $this->custom_permissions);
        }

        return false;
    }

    public function managedClients(): HasMany
    {
        return $this->hasMany(Client::class, 'assigned_manager_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->status === 'active';
    }

    /**
     * Filament Multi-tenancy: Tenants access
     */
    public function getTenants(Panel $panel): array|Collection
    {
        if ($this->isSuperAdmin()) {
            return Company::all();
        }

        return $this->company ? collect([$this->company]) : collect([]);
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->company_id === $tenant->id;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return null;
    }
}
