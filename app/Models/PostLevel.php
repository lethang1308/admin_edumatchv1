<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostLevel extends Model
{
    use HasFactory;

    protected $table = 'post_levels';

    protected $fillable = [
        'post_id',
        'level_id',
    ];

    protected $casts = [
        'id' => 'integer',
        'post_id' => 'integer',
        'level_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class, 'level_id');
    }
}
