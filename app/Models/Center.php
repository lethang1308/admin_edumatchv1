<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Center extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'centers';

    protected $fillable = [
        'owner_user_id',
        'name',
        'slug',
        'logo_path',
        'cover_path',
        'bio',
        'founded_year',
        'hotline',
        'contact_email',
        'registered_address',
        'province_id',
        'district_id',
        'ward_id',
        'verification_status',
        'verified_at',
        'rating_avg',
        'review_count',
        'trust_count',
    ];

    protected $attributes = [
        'verification_status' => 'unverified',
        'rating_avg' => 0,
        'review_count' => 0,
        'trust_count' => 0,
    ];

    protected $casts = [
        'id' => 'integer',
        'owner_user_id' => 'integer',
        'founded_year' => 'integer',
        'province_id' => 'integer',
        'district_id' => 'integer',
        'ward_id' => 'integer',
        'verified_at' => 'datetime',
        'rating_avg' => 'decimal:2',
        'review_count' => 'integer',
        'trust_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
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

    public function branches(): HasMany
    {
        return $this->hasMany(CenterBranch::class, 'center_id');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(CenterStaff::class, 'center_id');
    }

    public function verificationRequests(): HasMany
    {
        return $this->hasMany(VerificationRequest::class, 'center_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'center_id');
    }

    public function profileInteractions(): HasMany
    {
        return $this->hasMany(ProfileInteraction::class, 'center_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'center_id');
    }

    public function feedPosts(): HasMany
    {
        return $this->hasMany(FeedPost::class, 'center_id');
    }

    public function chatConversations(): HasMany
    {
        return $this->hasMany(ChatConversation::class, 'center_id');
    }

    public function homepageFeatures(): HasMany
    {
        return $this->hasMany(HomepageFeature::class, 'center_id');
    }
}
