<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostFavorite extends Model
{
    use HasFactory;

    protected $table = 'post_favorites';

    protected $fillable = [
        'student_user_id',
        'post_id',
    ];

    protected $casts = [
        'id' => 'integer',
        'student_user_id' => 'integer',
        'post_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }
}
