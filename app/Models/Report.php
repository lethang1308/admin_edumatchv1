<?php

namespace App\Models;

use App\Database\Relations\ReportTarget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    use HasFactory;

    // Teacher targets use users.id, like the teacher_user_id references in DBML.
    public const TARGET_MODELS = [
        'user' => User::class,
        'teacher' => User::class,
        'post' => Post::class,
        'center' => Center::class,
        'review' => Review::class,
        'feed_post' => FeedPost::class,
        'feed_comment' => FeedComment::class,
    ];

    protected $table = 'reports';

    protected $fillable = [
        'reporter_user_id',
        'target_type',
        'target_id',
        'reason',
        'description',
        'status',
        'handled_by',
        'admin_note',
        'resolved_at',
    ];

    protected $attributes = [
        'status' => 'open',
    ];

    protected $casts = [
        'id' => 'integer',
        'reporter_user_id' => 'integer',
        'target_id' => 'integer',
        'handled_by' => 'integer',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function target(): MorphTo
    {
        return $this->morphTo('target', 'target_type', 'target_id');
    }

    public static function getActualClassNameForMorph($class)
    {
        return self::TARGET_MODELS[$class]
            ?? throw new \InvalidArgumentException('Unsupported report target type: '.$class);
    }

    protected function newMorphTo(Builder $query, Model $parent, $foreignKey, $ownerKey, $type, $relation)
    {
        return new ReportTarget($query, $parent, $foreignKey, $ownerKey, $type, $relation);
    }
}
