<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryDocument extends BaseModel
{
    protected $table = 'library_documents';

    protected $fillable = [
        'company_id',
        'folder_id',
        'title',
        'description',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'keywords',
        'public_token',
        'is_encrypted',
        'version',
        'previous_version_id',
        'download_count',
        'last_downloaded_at',
    ];

    protected $casts = [
        'keywords' => 'array',
        'file_size' => 'integer',
        'is_encrypted' => 'boolean',
        'version' => 'integer',
        'download_count' => 'integer',
        'last_downloaded_at' => 'datetime',
    ];

    /**
     * Get the folder.
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(LibraryFolder::class, 'folder_id');
    }

    /**
     * Get previous version.
     */
    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_version_id');
    }

    /**
     * Generate public token for SDS sharing.
     */
    public function generatePublicToken(): void
    {
        $this->public_token = bin2hex(random_bytes(16));
        $this->save();
    }

    /**
     * Get public URL.
     */
    public function getPublicUrl(): ?string
    {
        if (!$this->public_token) {
            return null;
        }

        return url("/sds/{$this->public_token}");
    }

    /**
     * Get file extension.
     */
    public function getExtension(): string
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }

    /**
     * Get icon based on file type.
     */
    public function getIcon(): string
    {
        return match (strtolower($this->getExtension())) {
            'pdf' => 'document-text',
            'doc', 'docx' => 'document',
            'xls', 'xlsx' => 'table',
            'ppt', 'pptx' => 'presentation',
            'jpg', 'jpeg', 'png', 'gif' => 'photograph',
            'mp4', 'avi', 'mov' => 'video',
            default => 'document',
        };
    }

    /**
     * Check if file is image.
     */
    public function isImage(): bool
    {
        return in_array(strtolower($this->getExtension()), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }

    /**
     * Check if file is PDF.
     */
    public function isPdf(): bool
    {
        return strtolower($this->getExtension()) === 'pdf';
    }

    /**
     * Increment download count.
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
        $this->update(['last_downloaded_at' => now()]);
    }

    /**
     * Get formatted file size.
     */
    public function getFormattedSize(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }

    /**
     * Scope: Search by keywords.
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhereJsonContains('keywords', $term);
        });
    }

    /**
     * Scope: By folder.
     */
    public function scopeInFolder($query, ?int $folderId)
    {
        return $query->where('folder_id', $folderId);
    }

    /**
     * Scope: With public token.
     */
    public function scopePublic($query)
    {
        return $query->whereNotNull('public_token');
    }
}
