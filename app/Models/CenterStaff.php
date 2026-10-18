<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CenterStaff extends Model
{
    use HasFactory;

    protected $table = 'center_staff';

    protected $fillable = [
        'center_id',
        'full_name',
        'phone',
        'email',
        'status',
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    protected $casts = [
        'id' => 'integer',
        'center_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class, 'center_id');
    }

    public function consultationRequests(): HasMany
    {
        return $this->hasMany(ConsultationRequest::class, 'assigned_staff_id');
    }
}
