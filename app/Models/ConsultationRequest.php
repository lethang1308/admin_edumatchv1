<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsultationRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'consultation_requests';

    protected $fillable = [
        'student_user_id',
        'post_id',
        'batch_id',
        'assigned_staff_id',
        'contact_name',
        'contact_phone',
        'contact_email',
        'message',
        'status',
        'internal_note',
        'contacted_at',
        'closed_at',
    ];

    protected $attributes = [
        'status' => 'new',
    ];

    protected $casts = [
        'id' => 'integer',
        'student_user_id' => 'integer',
        'post_id' => 'integer',
        'batch_id' => 'integer',
        'assigned_staff_id' => 'integer',
        'contacted_at' => 'datetime',
        'closed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(CourseBatch::class, 'batch_id');
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(CenterStaff::class, 'assigned_staff_id');
    }

    public function requestStatusLogs(): HasMany
    {
        return $this->hasMany(RequestStatusLog::class, 'request_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class, 'request_id');
    }
}
