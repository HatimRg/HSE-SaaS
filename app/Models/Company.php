<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Company extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected $table = 'companies';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'domain',
        'email',
        'phone',
        'address',
        'registration_number',
        'settings',
        'color_primary_light',
        'color_primary_dark',
        'color_background_light',
        'color_background_dark',
        'color_accent',
        'logo_path',
        'is_active',
        'subscription_plan',
        'subscription_expires_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'subscription_expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden.
     */
    protected $hidden = [];

    /**
     * Boot the model without tenant scope.
     */
    protected static function boot(): void
    {
        parent::boot();
    }

    /**
     * Get users belonging to this company.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get projects belonging to this company.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get workers belonging to this company.
     */
    public function workers(): HasMany
    {
        return $this->hasMany(Worker::class);
    }

    /**
     * Get color palette settings.
     */
    public function getColorPalette(): array
    {
        return [
            'primaryLight' => $this->color_primary_light ?? '#3b82f6',
            'primaryDark' => $this->color_primary_dark ?? '#1d4ed8',
            'backgroundLight' => $this->color_background_light ?? '#ffffff',
            'backgroundDark' => $this->color_background_dark ?? '#0f172a',
            'accent' => $this->color_accent ?? '#f59e0b',
        ];
    }

    /**
     * Get settings with defaults.
     */
    public function getSettings(): array
    {
        return array_merge([
            'language' => 'fr',
            'timezone' => 'Africa/Casablanca',
            'date_format' => 'd/m/Y',
            'email_notifications' => true,
            'sms_notifications' => false,
            'two_factor_auth' => false,
            'session_timeout' => 120,
        ], $this->settings ?? []);
    }

    /**
     * Check if subscription is active.
     */
    public function isSubscriptionActive(): bool
    {
        return $this->is_active && 
               ($this->subscription_expires_at === null || 
                $this->subscription_expires_at->isFuture());
    }

    /**
     * Get cached company data.
     */
    public static function getCached(int $id): ?self
    {
        return Cache::remember("company:{$id}", 3600, function () use ($id) {
            return self::find($id);
        });
    }
}
