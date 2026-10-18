<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthVerificationToken extends Model
{
    use HasFactory;

    protected $table = 'auth_verification_tokens';

    protected $fillable = [
        'user_id',
        'destination',
        'channel',
        'purpose',
        'token_hash',
        'expires_at',
        'used_at',
        'attempt_count',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected $attributes = [
        'attempt_count' => 0,
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'attempt_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
