<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseBatch extends Model
{
    use HasFactory;

    protected $table = 'course_batches';

    protected $fillable = [
        'post_id',
        'center_branch_id',
        'teacher_user_id',
        'name',
        'capacity',
        'start_date',
        'end_date',
        'status',
    ];

    protected $attributes = [
        'status' => 'open',
    ];

    protected $casts = [
        'id' => 'integer',
        'post_id' => 'integer',
        'center_branch_id' => 'integer',
        'teacher_user_id' => 'integer',
        'capacity' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function centerBranch(): BelongsTo
    {
        return $this->belongsTo(CenterBranch::class, 'center_branch_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    public function courseSchedules(): HasMany
    {
        return $this->hasMany(CourseSchedule::class, 'batch_id');
    }

    public function consultationRequests(): HasMany
    {
        return $this->hasMany(ConsultationRequest::class, 'batch_id');
    }
}
