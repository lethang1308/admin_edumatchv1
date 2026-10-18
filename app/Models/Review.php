<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'reviews';

    protected $fillable = [
        'request_id',
        'student_user_id',
        'post_id',
        'teacher_user_id',
        'center_id',
        'rating',
        'comment',
        'status',
        'moderated_by',
        'moderated_at',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected $casts = [
        'id' => 'integer',
        'request_id' => 'integer',
        'student_user_id' => 'integer',
        'post_id' => 'integer',
        'teacher_user_id' => 'integer',
        'center_id' => 'integer',
        'rating' => 'integer',
        'moderated_by' => 'integer',
        'moderated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(ConsultationRequest::class, 'request_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class, 'center_id');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }
}
