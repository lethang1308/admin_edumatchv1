<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileInteraction extends Model
{
    use HasFactory;

    protected $table = 'profile_interactions';

    protected $fillable = [
        'student_user_id',
        'teacher_user_id',
        'center_id',
        'interaction_type',
    ];

    protected $casts = [
        'id' => 'integer',
        'student_user_id' => 'integer',
        'teacher_user_id' => 'integer',
        'center_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class, 'center_id');
    }
}
