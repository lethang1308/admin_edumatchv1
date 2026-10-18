<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherQualification extends Model
{
    use HasFactory;

    protected $table = 'teacher_qualifications';

    protected $fillable = [
        'teacher_user_id',
        'title',
        'issuer',
        'issued_on',
        'expires_on',
        'is_verified',
        'is_public',
    ];

    protected $attributes = [
        'is_verified' => false,
        'is_public' => true,
    ];

    protected $casts = [
        'id' => 'integer',
        'teacher_user_id' => 'integer',
        'issued_on' => 'date',
        'expires_on' => 'date',
        'is_verified' => 'boolean',
        'is_public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }
}
