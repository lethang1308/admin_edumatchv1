<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdministrativeArea extends Model
{
    use HasFactory;

    protected $table = 'administrative_areas';

    protected $fillable = [
        'parent_id',
        'code',
        'name',
        'level',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected $casts = [
        'id' => 'integer',
        'parent_id' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(AdministrativeArea::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(AdministrativeArea::class, 'parent_id');
    }

    public function provinceStudentProfiles(): HasMany
    {
        return $this->hasMany(StudentProfile::class, 'province_id');
    }

    public function districtStudentProfiles(): HasMany
    {
        return $this->hasMany(StudentProfile::class, 'district_id');
    }

    public function wardStudentProfiles(): HasMany
    {
        return $this->hasMany(StudentProfile::class, 'ward_id');
    }

    public function provinceTeacherProfiles(): HasMany
    {
        return $this->hasMany(TeacherProfile::class, 'province_id');
    }

    public function districtTeacherProfiles(): HasMany
    {
        return $this->hasMany(TeacherProfile::class, 'district_id');
    }

    public function wardTeacherProfiles(): HasMany
    {
        return $this->hasMany(TeacherProfile::class, 'ward_id');
    }

    public function provinceCenters(): HasMany
    {
        return $this->hasMany(Center::class, 'province_id');
    }

    public function districtCenters(): HasMany
    {
        return $this->hasMany(Center::class, 'district_id');
    }

    public function wardCenters(): HasMany
    {
        return $this->hasMany(Center::class, 'ward_id');
    }

    public function provinceCenterBranches(): HasMany
    {
        return $this->hasMany(CenterBranch::class, 'province_id');
    }

    public function districtCenterBranches(): HasMany
    {
        return $this->hasMany(CenterBranch::class, 'district_id');
    }

    public function wardCenterBranches(): HasMany
    {
        return $this->hasMany(CenterBranch::class, 'ward_id');
    }

    public function provincePosts(): HasMany
    {
        return $this->hasMany(Post::class, 'province_id');
    }

    public function districtPosts(): HasMany
    {
        return $this->hasMany(Post::class, 'district_id');
    }

    public function wardPosts(): HasMany
    {
        return $this->hasMany(Post::class, 'ward_id');
    }

    public function provinceSearchLogs(): HasMany
    {
        return $this->hasMany(SearchLog::class, 'province_id');
    }
}
