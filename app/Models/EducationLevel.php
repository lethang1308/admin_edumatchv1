<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EducationLevel extends Model
{
    use HasFactory;

    protected $table = 'education_levels';

    protected $fillable = [
        'code',
        'name',
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

    public function studentLearningNeeds(): HasMany
    {
        return $this->hasMany(StudentLearningNeed::class, 'level_id');
    }

    public function postLevels(): HasMany
    {
        return $this->hasMany(PostLevel::class, 'level_id');
    }

    public function searchLogs(): HasMany
    {
        return $this->hasMany(SearchLog::class, 'level_id');
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_levels', 'level_id', 'post_id')
            ->withPivot(['id'])->withTimestamps();
    }
}
