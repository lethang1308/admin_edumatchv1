<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeedPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'feed_posts';

    protected $fillable = [
        'author_user_id',
        'center_id',
        'title',
        'content',
        'status',
        'rejection_reason',
        'published_at',
        'like_count',
        'comment_count',
    ];

    protected $attributes = [
        'status' => 'pending',
        'like_count' => 0,
        'comment_count' => 0,
    ];

    protected $casts = [
        'id' => 'integer',
        'author_user_id' => 'integer',
        'center_id' => 'integer',
        'published_at' => 'datetime',
        'like_count' => 'integer',
        'comment_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class, 'center_id');
    }

    public function feedMedia(): HasMany
    {
        return $this->hasMany(FeedMedia::class, 'feed_post_id');
    }

    public function feedLikes(): HasMany
    {
        return $this->hasMany(FeedLike::class, 'feed_post_id');
    }

    public function feedComments(): HasMany
    {
        return $this->hasMany(FeedComment::class, 'feed_post_id');
    }

    public function likedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'feed_likes', 'feed_post_id', 'user_id')
            ->withPivot(['id'])->withTimestamps();
    }
}
