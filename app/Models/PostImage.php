<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostImage extends Model
{
    use HasFactory;

    protected $table = 'post_images';

    protected $fillable = [
        'post_id',
        'image_path',
        'alt_text',
        'is_cover',
        'sort_order',
    ];

    protected $attributes = [
        'is_cover' => false,
        'sort_order' => 0,
    ];

    protected $casts = [
        'id' => 'integer',
        'post_id' => 'integer',
        'is_cover' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }
}
