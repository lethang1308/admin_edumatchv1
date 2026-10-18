<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedMedia extends Model
{
    use HasFactory;

    protected $table = 'feed_media';

    protected $fillable = [
        'feed_post_id',
        'media_type',
        'file_path',
        'thumbnail_path',
        'sort_order',
    ];

    protected $attributes = [
        'sort_order' => 0,
    ];

    protected $casts = [
        'id' => 'integer',
        'feed_post_id' => 'integer',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function feedPost(): BelongsTo
    {
        return $this->belongsTo(FeedPost::class, 'feed_post_id');
    }
}
