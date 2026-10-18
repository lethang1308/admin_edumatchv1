<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'posts';

    protected $fillable = [
        'teacher_user_id',
        'center_id',
        'subject_id',
        'title',
        'slug',
        'short_description',
        'description',
        'target_audience',
        'outcomes',
        'teaching_mode',
        'price_amount',
        'price_unit',
        'schedule_text',
        'province_id',
        'district_id',
        'ward_id',
        'moderation_status',
        'enrollment_status',
        'rejection_reason',
        'published_at',
        'view_count',
    ];

    protected $attributes = [
        'price_unit' => 'course',
        'moderation_status' => 'draft',
        'enrollment_status' => 'open',
        'view_count' => 0,
    ];

    protected $casts = [
        'id' => 'integer',
        'teacher_user_id' => 'integer',
        'center_id' => 'integer',
        'subject_id' => 'integer',
        'price_amount' => 'decimal:2',
        'province_id' => 'integer',
        'district_id' => 'integer',
        'ward_id' => 'integer',
        'published_at' => 'datetime',
        'view_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class, 'center_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
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

    public function postLevels(): HasMany
    {
        return $this->hasMany(PostLevel::class, 'post_id');
    }

    public function postImages(): HasMany
    {
        return $this->hasMany(PostImage::class, 'post_id');
    }

    public function courseBatches(): HasMany
    {
        return $this->hasMany(CourseBatch::class, 'post_id');
    }

    public function postModerationLogs(): HasMany
    {
        return $this->hasMany(PostModerationLog::class, 'post_id');
    }

    public function consultationRequests(): HasMany
    {
        return $this->hasMany(ConsultationRequest::class, 'post_id');
    }

    public function postFavorites(): HasMany
    {
        return $this->hasMany(PostFavorite::class, 'post_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'post_id');
    }

    public function chatConversations(): HasMany
    {
        return $this->hasMany(ChatConversation::class, 'post_id');
    }

    public function homepageFeatures(): HasMany
    {
        return $this->hasMany(HomepageFeature::class, 'post_id');
    }

    public function levels(): BelongsToMany
    {
        return $this->belongsToMany(EducationLevel::class, 'post_levels', 'post_id', 'level_id')
            ->withPivot(['id'])->withTimestamps();
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_favorites', 'post_id', 'student_user_id')
            ->withPivot(['id'])->withTimestamps();
    }
}
