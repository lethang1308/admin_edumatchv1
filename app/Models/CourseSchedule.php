<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseSchedule extends Model
{
    use HasFactory;

    protected $table = 'course_schedules';

    protected $fillable = [
        'batch_id',
        'weekday',
        'start_time',
        'end_time',
        'delivery_mode',
        'note',
    ];

    protected $casts = [
        'id' => 'integer',
        'batch_id' => 'integer',
        'weekday' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(CourseBatch::class, 'batch_id');
    }
}
