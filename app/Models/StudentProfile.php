<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    use HasFactory;

    protected $table = 'student_profiles';

    protected $fillable = [
        'user_id',
        'full_name',
        'date_of_birth',
        'province_id',
        'district_id',
        'ward_id',
        'bio',
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'date_of_birth' => 'date',
        'province_id' => 'integer',
        'district_id' => 'integer',
        'ward_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(AdministrativeArea::class, 'province_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(AdministrativeArea::class, 'district_id');
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(AdministrativeArea::class, 'ward_id');
    }
}
