<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VerificationRequest extends Model
{
    use HasFactory;

    protected $table = 'verification_requests';

    protected $fillable = [
        'teacher_user_id',
        'center_id',
        'submitted_by_user_id',
        'status',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected $casts = [
        'id' => 'integer',
        'teacher_user_id' => 'integer',
        'center_id' => 'integer',
        'submitted_by_user_id' => 'integer',
        'submitted_at' => 'datetime',
        'reviewed_by' => 'integer',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class, 'center_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function verificationDocuments(): HasMany
    {
        return $this->hasMany(VerificationDocument::class, 'request_id');
    }
}
