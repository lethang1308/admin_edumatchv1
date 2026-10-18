<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentLearningNeed extends Model
{
    use HasFactory;

    protected $table = 'student_learning_needs';

    protected $fillable = [
        'student_user_id',
        'subject_id',
        'level_id',
        'preferred_mode',
        'budget_min',
        'budget_max',
        'goals',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected $casts = [
        'id' => 'integer',
        'student_user_id' => 'integer',
        'subject_id' => 'integer',
        'level_id' => 'integer',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class, 'level_id');
    }
}
