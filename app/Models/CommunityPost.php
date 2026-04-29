<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityPost extends BaseModel
{
    protected $table = 'community_posts';

    protected $fillable = [
        'company_id',
        'user_id',
        'content',
        'image_paths',
        'hashtags',
        'mentions',
        'is_pinned',
        'is_announcement',
        'likes_count',
        'comments_count',
        'shares_count',
        'edited_at',
    ];

    protected $casts = [
        'image_paths' => 'array',
        'hashtags' => 'array',
        'mentions' => 'array',
        'is_pinned' => 'boolean',
        'is_announcement' => 'boolean',
        'edited_at' => 'datetime',
    ];

    /**
     * Get the author.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get comments.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'post_id');
    }

    /**
     * Get reactions.
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class, 'post_id');
    }

    /**
     * Increment likes count.
     */
    public function incrementLikes(): void
    {
        $this->increment('likes_count');
    }

    /**
     * Decrement likes count.
     */
    public function decrementLikes(): void
    {
        $this->decrement('likes_count');
    }

    /**
     * Check if user has liked.
     */
    public function isLikedBy(int $userId): bool
    {
        return $this->reactions()
                    ->where('user_id', $userId)
                    ->where('type', 'like')
                    ->exists();
    }

    /**
     * Mark as edited.
     */
    public function markAsEdited(): void
    {
        $this->update(['edited_at' => now()]);
    }

    /**
     * Scope: Announcements.
     */
    public function scopeAnnouncements($query)
    {
        return $query->where('is_announcement', true);
    }

    /**
     * Scope: Pinned.
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope: By hashtag.
     */
    public function scopeByHashtag($query, string $hashtag)
    {
        return $query->whereJsonContains('hashtags', $hashtag);
    }
}
