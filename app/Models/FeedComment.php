<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeedComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'feed_comments';

    protected $fillable = [
        'feed_post_id',
        'user_id',
        'parent_id',
        'content',
        'status',
    ];

    protected $attributes = [
        'status' => 'published',
    ];

    protected $casts = [
        'id' => 'integer',
        'feed_post_id' => 'integer',
        'user_id' => 'integer',
        'parent_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function feedPost(): BelongsTo
    {
        return $this->belongsTo(FeedPost::class, 'feed_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(FeedComment::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(FeedComment::class, 'parent_id');
    }
}
