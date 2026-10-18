<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostModerationLog extends Model
{
    use HasFactory;

    protected $table = 'post_moderation_logs';

    protected $fillable = [
        'post_id',
        'action_by_user_id',
        'from_status',
        'to_status',
        'reason',
    ];

    protected $casts = [
        'id' => 'integer',
        'post_id' => 'integer',
        'action_by_user_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function actionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_by_user_id');
    }
}
