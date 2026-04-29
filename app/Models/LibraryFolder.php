<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibraryFolder extends BaseModel
{
    protected $table = 'library_folders';

    protected $fillable = [
        'company_id',
        'parent_id',
        'name',
        'description',
        'sort_order',
        'is_protected',
    ];

    protected $casts = [
        'is_protected' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get parent folder.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get child folders.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
                    ->orderBy('sort_order')
                    ->orderBy('name');
    }

    /**
     * Get documents in this folder.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(LibraryDocument::class, 'folder_id')
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Get full path as array.
     */
    public function getPath(): array
    {
        $path = [$this];
        $current = $this;

        while ($current->parent) {
            $path[] = $current->parent;
            $current = $current->parent;
        }

        return array_reverse($path);
    }

    /**
     * Get path as string.
     */
    public function getPathString(string $separator = ' / '): string
    {
        return collect($this->getPath())
            ->pluck('name')
            ->join($separator);
    }

    /**
     * Check if folder is root.
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Get all descendants recursively.
     */
    public function getAllDescendants(): \Illuminate\Support\Collection
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getAllDescendants());
        }

        return $descendants;
    }

    /**
     * Scope: Root folders only.
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: Ordered.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')
                     ->orderBy('name');
    }
}
