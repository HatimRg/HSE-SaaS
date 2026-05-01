import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { motion, AnimatePresence } from 'framer-motion';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  MessageSquare,
  Heart,
  ThumbsUp,
  Laugh,
  Frown,
  Angry,
  Send,
  Image as ImageIcon,
  Hash,
  Search,
  Plus,
  TrendingUp,
  Users,
  MoreHorizontal,
  Pin,
} from 'lucide-react';
import { api } from '../lib/api';
import { EmptyState } from '../components/empty-state';

export default function CommunityPage() {
  const { t } = useTranslation();
  const [newPost, setNewPost] = useState('');
  const [selectedPost, setSelectedPost] = useState<number | null>(null);
  const [showCreatePost, setShowCreatePost] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedHashtag, setSelectedHashtag] = useState<string | null>(null);
  const queryClient = useQueryClient();

  // Fetch community posts
  const { data: posts, isLoading } = useQuery({
    queryKey: ['community-posts', searchQuery, selectedHashtag],
    queryFn: async () => {
      const params = new URLSearchParams();
      if (searchQuery) params.append('search', searchQuery);
      if (selectedHashtag) params.append('hashtag', selectedHashtag);
      
      const response = await api.get(`/community?${params}`);
      return response.data.data;
    },
  });

  // Fetch trending hashtags
  const { data: trendingHashtags } = useQuery({
    queryKey: ['trending-hashtags'],
    queryFn: async () => {
      const response = await api.get('/community/trending-hashtags');
      return response.data.data;
    },
  });

  // Create post mutation
  const createPostMutation = useMutation({
    mutationFn: async (postData: any) => {
      const response = await api.post('/community', postData);
      return response.data.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['community-posts'] });
      setShowCreatePost(false);
      setNewPost('');
    },
  });

  // Add comment mutation
  const addCommentMutation = useMutation({
    mutationFn: async ({ postId, content }: { postId: number; content: string }) => {
      const response = await api.post(`/community/${postId}/comments`, { content });
      return response.data.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['community-posts'] });
    },
  });

  // Add reaction mutation
  const addReactionMutation = useMutation({
    mutationFn: async ({ postId, reactionType }: { postId: number; reactionType: string }) => {
      const response = await api.post(`/community/${postId}/reactions`, { reaction_type: reactionType });
      return response.data.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['community-posts'] });
    },
  });

  const handleCreatePost = () => {
    if (newPost.trim()) {
      createPostMutation.mutate({
        content: newPost,
        title: newPost.split('\n')[0]?.substring(0, 100) || null,
      });
    }
  };

  const handleAddComment = (postId: number, content: string) => {
    if (content.trim()) {
      addCommentMutation.mutate({ postId, content });
    }
  };

  const handleAddReaction = (postId: number, reactionType: string) => {
    addReactionMutation.mutate({ postId, reactionType });
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <motion.div
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
        className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
      >
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Community</h1>
          <p className="text-muted-foreground">
            Connect with your team and share experiences
          </p>
        </div>

        <div className="flex items-center gap-4">
          {/* Search */}
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <input
              type="text"
              placeholder="Search posts..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="pl-10 pr-4 py-2 rounded-lg border border-border bg-background focus:outline-none focus:ring-2 focus:ring-primary"
            />
          </div>

          {/* Create Post */}
          <button
            onClick={() => setShowCreatePost(true)}
            className="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90"
          >
            <Plus className="h-4 w-4" />
            New Post
          </button>
        </div>
      </motion.div>

      <div className="grid gap-6 lg:grid-cols-4">
        {/* Main Content */}
        <div className="lg:col-span-3 space-y-6">
          {/* Create Post Card */}
          {showCreatePost && (
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              className="rounded-xl border border-border bg-card p-6"
            >
              <textarea
                value={newPost}
                onChange={(e) => setNewPost(e.target.value)}
                placeholder="Share your thoughts, experiences, or ask questions..."
                className="w-full p-4 border border-border rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-primary bg-background"
                rows={4}
              />
              
              <div className="flex items-center justify-between mt-4">
                <div className="flex items-center gap-2">
                  <button className="p-2 rounded-lg hover:bg-muted">
                    <ImageIcon className="h-5 w-5" />
                  </button>
                </div>
                
                <div className="flex items-center gap-2">
                  <button
                    onClick={() => setShowCreatePost(false)}
                    className="px-4 py-2 text-muted-foreground hover:bg-muted rounded-lg"
                  >
                    Cancel
                  </button>
                  <button
                    onClick={handleCreatePost}
                    disabled={!newPost.trim() || createPostMutation.isPending}
                    className="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 disabled:opacity-50"
                  >
                    {createPostMutation.isPending ? 'Posting...' : 'Post'}
                  </button>
                </div>
              </div>
            </motion.div>
          )}

          {/* Posts */}
          <div className="space-y-4">
            {isLoading ? (
              [1, 2, 3].map((i) => <PostSkeleton key={i} />)
            ) : posts?.data?.length > 0 ? (
              posts.data.map((post: any) => (
                <PostCard
                  key={post.id}
                  post={post}
                  onComment={handleAddComment}
                  onReaction={handleAddReaction}
                />
              ))
            ) : (
              <EmptyState
                title="No posts yet"
                description="Be the first to share something with the community"
              />
            )}
          </div>
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          {/* Trending Hashtags */}
          <div className="rounded-xl border border-border bg-card p-6">
            <h3 className="font-semibold mb-4 flex items-center gap-2">
              <TrendingUp className="h-4 w-4" />
              Trending Topics
            </h3>
            {trendingHashtags?.length > 0 ? (
              <div className="space-y-2">
                {trendingHashtags.slice(0, 10).map((hashtag: any, index: number) => (
                  <button
                    key={index}
                    onClick={() => setSelectedHashtag(hashtag.tag)}
                    className={`flex items-center justify-between w-full p-2 rounded-lg hover:bg-muted transition-colors ${
                      selectedHashtag === hashtag.tag ? 'bg-primary/10 text-primary' : ''
                    }`}
                  >
                    <div className="flex items-center gap-2">
                      <Hash className="h-4 w-4" />
                      <span className="text-sm">#{hashtag.tag}</span>
                    </div>
                    <span className="text-xs text-muted-foreground">{hashtag.count}</span>
                  </button>
                ))}
              </div>
            ) : (
              <p className="text-sm text-muted-foreground">No trending topics yet</p>
            )}
          </div>

          {/* Community Stats */}
          <div className="rounded-xl border border-border bg-card p-6">
            <h3 className="font-semibold mb-4 flex items-center gap-2">
              <Users className="h-4 w-4" />
              Community Stats
            </h3>
            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-sm text-muted-foreground">Total Posts</span>
                <span className="font-semibold">{posts?.total || 0}</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-sm text-muted-foreground">Active Members</span>
                <span className="font-semibold">42</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-sm text-muted-foreground">This Week</span>
                <span className="font-semibold">{posts?.this_week || 0}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

// Post Card Component
function PostCard({ post, onComment, onReaction }: any) {
  const [commentText, setCommentText] = useState('');
  const [showComments, setShowComments] = useState(false);

  const reactions = [
    { type: 'like', icon: ThumbsUp, color: 'text-blue-500' },
    { type: 'love', icon: Heart, color: 'text-red-500' },
    { type: 'laugh', icon: Laugh, color: 'text-yellow-500' },
    { type: 'sad', icon: Frown, color: 'text-blue-400' },
    { type: 'angry', icon: Angry, color: 'text-red-600' },
  ];

  const handleComment = () => {
    if (commentText.trim()) {
      onComment(post.id, commentText);
      setCommentText('');
    }
  };

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      className="rounded-xl border border-border bg-card overflow-hidden"
    >
      {/* Post Header */}
      <div className="p-6">
        <div className="flex items-start justify-between mb-4">
          <div className="flex items-center gap-3">
            <div className="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
              <span className="text-sm font-bold text-primary">
                {post.author?.name?.charAt(0)?.toUpperCase() || 'A'}
              </span>
            </div>
            <div>
              <h3 className="font-semibold">{post.author?.name}</h3>
              <div className="flex items-center gap-2 text-sm text-muted-foreground">
                <span>{post.author?.role}</span>
                <span>•</span>
                <span>{new Date(post.created_at).toLocaleDateString()}</span>
                {post.pinned && (
                  <>
                    <span>•</span>
                    <Pin className="h-3 w-3" />
                  </>
                )}
              </div>
            </div>
          </div>
          
          <button className="p-1 rounded hover:bg-muted">
            <MoreHorizontal className="h-4 w-4" />
          </button>
        </div>

        {/* Post Content */}
        <div className="mb-4">
          {post.title && <h2 className="font-semibold text-lg mb-2">{post.title}</h2>}
          <p className="text-foreground whitespace-pre-wrap">{post.content}</p>
        </div>

        {/* Post Images */}
        {post.images?.length > 0 && (
          <div className="grid gap-2 mb-4">
            {post.images.map((image: any, index: number) => (
              <img
                key={index}
                src={`/storage/${image.file_path}`}
                alt={image.file_name}
                className="rounded-lg max-h-64 object-cover"
              />
            ))}
          </div>
        )}

        {/* Hashtags */}
        {post.hashtags?.length > 0 && (
          <div className="flex flex-wrap gap-2 mb-4">
            {post.hashtags.map((hashtag: any, index: number) => (
              <span
                key={index}
                className="px-2 py-1 bg-primary/10 text-primary text-xs rounded-full cursor-pointer hover:bg-primary/20"
              >
                #{hashtag.tag}
              </span>
            ))}
          </div>
        )}

        {/* Reactions */}
        <div className="flex items-center justify-between border-t border-border pt-4">
          <div className="flex items-center gap-2">
            {reactions.map((reaction) => {
              const Icon = reaction.icon;
              const userReaction = post.reactions?.find((r: any) => r.user_id === 1); // Replace with actual user ID
              const isActive = userReaction?.reaction_type === reaction.type;
              const count = post.reactions?.filter((r: any) => r.reaction_type === reaction.type).length || 0;

              return (
                <button
                  key={reaction.type}
                  onClick={() => onReaction(post.id, reaction.type)}
                  className={`flex items-center gap-1 px-2 py-1 rounded-lg transition-colors ${
                    isActive
                      ? 'bg-primary/10 text-primary'
                      : 'hover:bg-muted text-muted-foreground'
                  }`}
                >
                  <Icon className={`h-4 w-4 ${isActive ? reaction.color : ''}`} />
                  {count > 0 && <span className="text-xs">{count}</span>}
                </button>
              );
            })}
          </div>

          <div className="flex items-center gap-4">
            <button
              onClick={() => setShowComments(!showComments)}
              className="flex items-center gap-1 text-muted-foreground hover:text-foreground"
            >
              <MessageSquare className="h-4 w-4" />
              <span className="text-sm">{post.comments?.length || 0}</span>
            </button>
          </div>
        </div>
      </div>

      {/* Comments Section */}
      <AnimatePresence>
        {showComments && (
          <motion.div
            initial={{ height: 0, opacity: 0 }}
            animate={{ height: 'auto', opacity: 1 }}
            exit={{ height: 0, opacity: 0 }}
            className="border-t border-border"
          >
            <div className="p-6 space-y-4">
              {/* Add Comment */}
              <div className="flex gap-2">
                <input
                  type="text"
                  value={commentText}
                  onChange={(e) => setCommentText(e.target.value)}
                  placeholder="Add a comment..."
                  className="flex-1 px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary bg-background"
                  onKeyPress={(e) => e.key === 'Enter' && handleComment()}
                />
                <button
                  onClick={handleComment}
                  disabled={!commentText.trim()}
                  className="p-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 disabled:opacity-50"
                >
                  <Send className="h-4 w-4" />
                </button>
              </div>

              {/* Comments */}
              {post.comments?.length > 0 ? (
                <div className="space-y-3">
                  {post.comments.map((comment: any, index: number) => (
                    <div key={index} className="flex gap-3">
                      <div className="h-8 w-8 rounded-full bg-muted flex items-center justify-center flex-shrink-0">
                        <span className="text-xs font-bold">
                          {comment.author?.name?.charAt(0)?.toUpperCase() || 'A'}
                        </span>
                      </div>
                      <div className="flex-1">
                        <div className="flex items-center gap-2 mb-1">
                          <span className="font-medium text-sm">{comment.author?.name}</span>
                          <span className="text-xs text-muted-foreground">
                            {new Date(comment.created_at).toLocaleDateString()}
                          </span>
                        </div>
                        <p className="text-sm">{comment.content}</p>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <p className="text-sm text-muted-foreground text-center py-4">
                  No comments yet. Be the first to comment!
                </p>
              )}
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </motion.div>
  );
}

// Post Skeleton Component
function PostSkeleton() {
  return (
    <div className="rounded-xl border border-border bg-card p-6 space-y-4">
      <div className="flex items-center gap-3">
        <div className="h-10 w-10 rounded-full bg-muted animate-pulse" />
        <div className="space-y-2">
          <div className="h-4 w-32 bg-muted rounded animate-pulse" />
          <div className="h-3 w-24 bg-muted rounded animate-pulse" />
        </div>
      </div>
      <div className="space-y-2">
        <div className="h-4 w-full bg-muted rounded animate-pulse" />
        <div className="h-4 w-3/4 bg-muted rounded animate-pulse" />
      </div>
      <div className="flex items-center gap-2">
        <div className="h-8 w-16 bg-muted rounded animate-pulse" />
        <div className="h-8 w-16 bg-muted rounded animate-pulse" />
      </div>
    </div>
  );
}
