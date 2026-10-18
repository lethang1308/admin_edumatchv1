<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchLog extends Model
{
    use HasFactory;

    protected $table = 'search_logs';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'keyword',
        'subject_id',
        'level_id',
        'teaching_mode',
        'province_id',
        'result_count',
    ];

    protected $attributes = [
        'result_count' => 0,
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'subject_id' => 'integer',
        'level_id' => 'integer',
        'province_id' => 'integer',
        'result_count' => 'integer',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class, 'level_id');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(AdministrativeArea::class, 'province_id');
    }
}
