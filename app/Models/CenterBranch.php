<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CenterBranch extends Model
{
    use HasFactory;

    protected $table = 'center_branches';

    protected $fillable = [
        'center_id',
        'name',
        'address_line',
        'province_id',
        'district_id',
        'ward_id',
        'latitude',
        'longitude',
        'is_main',
        'status',
    ];

    protected $attributes = [
        'is_main' => false,
        'status' => 'active',
    ];

    protected $casts = [
        'id' => 'integer',
        'center_id' => 'integer',
        'province_id' => 'integer',
        'district_id' => 'integer',
        'ward_id' => 'integer',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_main' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class, 'center_id');
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

    public function courseBatches(): HasMany
    {
        return $this->hasMany(CourseBatch::class, 'center_branch_id');
    }
}
