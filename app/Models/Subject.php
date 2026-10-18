<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    protected $table = 'subjects';

    protected $fillable = [
        'name',
        'slug',
        'icon_path',
        'sort_order',
        'is_active',
    ];

    protected $attributes = [
        'sort_order' => 0,
        'is_active' => true,
    ];

    protected $casts = [
        'id' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function teacherSubjects(): HasMany
    {
        return $this->hasMany(TeacherSubject::class, 'subject_id');
    }

    public function studentLearningNeeds(): HasMany
    {
        return $this->hasMany(StudentLearningNeed::class, 'subject_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'subject_id');
    }

    public function searchLogs(): HasMany
    {
        return $this->hasMany(SearchLog::class, 'subject_id');
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'teacher_subjects', 'subject_id', 'teacher_user_id')
            ->withPivot(['id', 'level_note'])->withTimestamps();
    }
}
