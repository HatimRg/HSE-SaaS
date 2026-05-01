<?php

namespace App\Http\Controllers\Api;

use App\Models\CommunityPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CommunityController extends BaseController
{
    /**
     * Display a listing of community posts.
     */
    public function index(Request $request)
    {
        $posts = CommunityPost::with(['author', 'comments.author', 'reactions.user'])
            ->when($request->project_id, function ($query, $projectId) {
                $query->where('project_id', $projectId);
            })
            ->when($request->hashtag, function ($query, $hashtag) {
                $query->where('content', 'like', "%#{$hashtag}%");
            })
            ->when($request->search, function ($query, $search) {
                $query->where('content', 'like', "%{$search}%")
                      ->orWhere('title', 'like', "%{$search}%");
            })
            ->orderBy('pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return $this->successResponse($posts);
    }

    /**
     * Store a newly created community post.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|max:5000',
            'project_id' => 'nullable|exists:projects,id',
            'images' => 'nullable|array',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'hashtags' => 'nullable|array',
            'hashtags.*' => 'string|max:50',
            'pinned' => 'boolean',
            'allow_comments' => 'boolean',
        ]);

        $post = CommunityPost::create([
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'],
            'project_id' => $validated['project_id'] ?? null,
            'author_id' => auth()->id(),
            'company_id' => auth()->user()->company_id,
            'pinned' => $validated['pinned'] ?? false,
            'allow_comments' => $validated['allow_comments'] ?? true,
        ]);

        // Handle image uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('community-images', 'public');
                $post->images()->create([
                    'file_path' => $path,
                    'file_name' => $image->getClientOriginalName(),
                    'file_type' => $image->getClientMimeType(),
                    'file_size' => $image->getSize(),
                ]);
            }
        }

        // Extract and store hashtags
        $hashtags = $this->extractHashtags($validated['content']);
        if (!empty($validated['hashtags'] ?? [])) {
            $hashtags = array_merge($hashtags, $validated['hashtags']);
        }
        $hashtags = array_unique($hashtags);

        foreach ($hashtags as $hashtag) {
            $post->hashtags()->create(['tag' => $hashtag]);
        }

        $post->load(['author', 'images', 'hashtags']);

        $this->logActivity('community_post_created', $post, $validated);

        return $this->successResponse($post, 'Post created successfully');
    }

    /**
     * Display the specified community post.
     */
    public function show(CommunityPost $post)
    {
        $post->load(['author', 'images', 'hashtags', 'comments.author', 'reactions.user']);

        return $this->successResponse($post);
    }

    /**
     * Update the specified community post.
     */
    public function update(Request $request, CommunityPost $post)
    {
        if ($post->author_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'sometimes|string|max:5000',
            'pinned' => 'boolean',
            'allow_comments' => 'boolean',
        ]);

        $post->update($validated);

        // Update hashtags if content changed
        if (isset($validated['content'])) {
            $post->hashtags()->delete();
            $hashtags = $this->extractHashtags($validated['content']);
            foreach ($hashtags as $hashtag) {
                $post->hashtags()->create(['tag' => $hashtag]);
            }
        }

        $post->load(['author', 'images', 'hashtags']);

        $this->logActivity('community_post_updated', $post, $validated);

        return $this->successResponse($post, 'Post updated successfully');
    }

    /**
     * Remove the specified community post.
     */
    public function destroy(CommunityPost $post)
    {
        if ($post->author_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return $this->errorResponse('Unauthorized', 403);
        }

        // Delete associated images
        foreach ($post->images as $image) {
            Storage::disk('public')->delete($image->file_path);
        }

        $post->delete();

        $this->logActivity('community_post_deleted', $post);

        return $this->successResponse(null, 'Post deleted successfully');
    }

    /**
     * Add comment to post.
     */
    public function addComment(Request $request, CommunityPost $post)
    {
        if (!$post->allow_comments) {
            return $this->errorResponse('Comments are not allowed for this post', 400);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:community_comments,id',
        ]);

        $comment = $post->comments()->create([
            'content' => $validated['content'],
            'author_id' => auth()->id(),
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        $comment->load(['author']);

        $this->logActivity('community_comment_added', $post, $validated);

        return $this->successResponse($comment, 'Comment added successfully');
    }

    /**
     * Update comment.
     */
    public function updateComment(Request $request, CommunityPost $post, $commentId)
    {
        $comment = $post->comments()->findOrFail($commentId);

        if ($comment->author_id !== auth()->id()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment->update($validated);

        $comment->load(['author']);

        $this->logActivity('community_comment_updated', $post, $validated);

        return $this->successResponse($comment, 'Comment updated successfully');
    }

    /**
     * Delete comment.
     */
    public function deleteComment(CommunityPost $post, $commentId)
    {
        $comment = $post->comments()->findOrFail($commentId);

        if ($comment->author_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $comment->delete();

        $this->logActivity('community_comment_deleted', $post);

        return $this->successResponse(null, 'Comment deleted successfully');
    }

    /**
     * Add reaction to post.
     */
    public function addReaction(Request $request, CommunityPost $post)
    {
        $validated = $request->validate([
            'reaction_type' => 'required|in:like,love,laugh,wow,sad,angry',
        ]);

        // Remove existing reaction if any
        $post->reactions()->where('user_id', auth()->id())->delete();

        // Add new reaction
        $reaction = $post->reactions()->create([
            'reaction_type' => $validated['reaction_type'],
            'user_id' => auth()->id(),
        ]);

        $reaction->load(['user']);

        $this->logActivity('community_reaction_added', $post, $validated);

        return $this->successResponse($reaction, 'Reaction added successfully');
    }

    /**
     * Remove reaction from post.
     */
    public function removeReaction(CommunityPost $post)
    {
        $post->reactions()->where('user_id', auth()->id())->delete();

        $this->logActivity('community_reaction_removed', $post);

        return $this->successResponse(null, 'Reaction removed successfully');
    }

    /**
     * Get trending hashtags.
     */
    public function trendingHashtags()
    {
        $trending = \DB::table('community_hashtags')
            ->select('tag', \DB::raw('COUNT(*) as count'))
            ->join('community_posts', 'community_hashtags.community_post_id', '=', 'community_posts.id')
            ->where('community_posts.created_at', '>=', now()->subDays(7))
            ->groupBy('tag')
            ->orderBy('count', 'desc')
            ->limit(20)
            ->get();

        return $this->successResponse($trending);
    }

    /**
     * Get community statistics.
     */
    public function statistics()
    {
        $stats = [
            'total_posts' => CommunityPost::where('company_id', auth()->user()->company_id)->count(),
            'posts_this_week' => CommunityPost::where('company_id', auth()->user()->company_id)
                ->where('created_at', '>=', now()->subWeek())
                ->count(),
            'total_comments' => \DB::table('community_comments')
                ->join('community_posts', 'community_comments.community_post_id', '=', 'community_posts.id')
                ->where('community_posts.company_id', auth()->user()->company_id)
                ->count(),
            'total_reactions' => \DB::table('community_reactions')
                ->join('community_posts', 'community_reactions.community_post_id', '=', 'community_posts.id')
                ->where('community_posts.company_id', auth()->user()->company_id)
                ->count(),
            'most_active_users' => CommunityPost::where('company_id', auth()->user()->company_id)
                ->with('author')
                ->selectRaw('author_id, COUNT(*) as post_count')
                ->groupBy('author_id')
                ->orderBy('post_count', 'desc')
                ->limit(5)
                ->get(),
            'recent_activity' => CommunityPost::where('company_id', auth()->user()->company_id)
                ->with(['author'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
        ];

        return $this->successResponse($stats);
    }

    /**
     * Extract hashtags from content.
     */
    private function extractHashtags(string $content): array
    {
        preg_match_all('/#(\w+)/', $content, $matches);
        return array_map('strtolower', $matches[1]);
    }
}
