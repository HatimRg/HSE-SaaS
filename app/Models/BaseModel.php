<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

abstract class BaseModel extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that should be encrypted.
     */
    protected array $encrypted = [];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Note: Tenant scoping is handled by TenantMiddleware, not here.
        // Adding it here would cause double WHERE clauses.

        // Set company_id on create
        static::creating(function ($model) {
            if (auth()->check() && !isset($model->company_id)) {
                $model->company_id = auth()->user()->company_id;
            }
            
            if (auth()->check() && !isset($model->user_id)) {
                $model->user_id = auth()->user()->id;
            }
        });

        // Encrypt sensitive fields before saving
        static::saving(function ($model) {
            if (config('app.enable_e2e_encryption') && !empty($model->encrypted)) {
                $encryptionService = app(\App\Services\EncryptionService::class);
                
                foreach ($model->encrypted as $field) {
                    if (isset($model->attributes[$field]) && !empty($model->attributes[$field])) {
                        $result = $encryptionService->encrypt($model->attributes[$field]);
                        
                        if ($result['success']) {
                            $model->attributes[$field] = json_encode([
                                'data' => $result['data'],
                                'iv' => $result['iv'],
                                'cipher' => $result['cipher'],
                            ]);
                        }
                    }
                }
            }
        });

        // Clear cache on save
        static::saved(function ($model) {
            $model->clearModelCache();
        });

        // Clear cache on delete
        static::deleted(function ($model) {
            $model->clearModelCache();
        });
    }

    /**
     * Get an attribute with automatic decryption if needed.
     */
    public function getAttribute($key): mixed
    {
        $value = parent::getAttribute($key);

        // Decrypt if this is an encrypted field
        if (in_array($key, $this->encrypted) && is_string($value)) {
            $decoded = json_decode($value, true);
            
            if (is_array($decoded) && isset($decoded['data']) && isset($decoded['iv'])) {
                $encryptionService = app(\App\Services\EncryptionService::class);
                $result = $encryptionService->decrypt($decoded['data'], $decoded['iv']);
                
                if ($result['success']) {
                    return $result['data'];
                }
            }
        }

        return $value;
    }

    /**
     * Clear cache entries related to this model.
     */
    public function clearModelCache(): void
    {
        $cacheKey = $this->getCacheKey();
        
        try {
            Cache::forget($cacheKey);
            Cache::forget("{$cacheKey}:list");
            // Cache::tags() not supported by file/database drivers - skip
        } catch (\Exception $e) {
            Log::warning('Cache clear failed', ['key' => $cacheKey, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Get cache key for this model instance.
     */
    public function getCacheKey(): string
    {
        return "{$this->getTable()}:{$this->getKey()}";
    }

    /**
     * Scope: Filter by company explicitly (for admin operations).
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope: Without tenant scope (for super admin - no-op since scope removed from model).
     */
    public function scopeWithoutTenant($query)
    {
        return $query;
    }

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        return $this->toArray();
    }
}
