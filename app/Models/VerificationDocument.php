<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationDocument extends Model
{
    use HasFactory;

    protected $table = 'verification_documents';

    protected $fillable = [
        'request_id',
        'document_type',
        'document_name',
        'issuing_authority',
        'issued_year',
        'file_path',
        'original_name',
        'mime_type',
        'status',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected $hidden = ['file_path'];

    protected $casts = [
        'id' => 'integer',
        'request_id' => 'integer',
        'issued_year' => 'integer',
        'reviewed_by' => 'integer',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(VerificationRequest::class, 'request_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
