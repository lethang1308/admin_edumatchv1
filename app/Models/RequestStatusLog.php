<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestStatusLog extends Model
{
    use HasFactory;

    protected $table = 'request_status_logs';

    protected $fillable = [
        'request_id',
        'changed_by_user_id',
        'from_status',
        'to_status',
        'note',
    ];

    protected $casts = [
        'id' => 'integer',
        'request_id' => 'integer',
        'changed_by_user_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(ConsultationRequest::class, 'request_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
