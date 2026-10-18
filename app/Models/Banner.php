<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $table = 'banners';

    protected $fillable = [
        'title',
        'image_path',
        'link_url',
        'sort_order',
        'status',
        'starts_at',
        'ends_at',
        'image',
        'link',
        'order',
        'start_date',
        'end_date',
        'position',
    ];

    protected $attributes = [
        'sort_order' => 0,
        'status' => 'active',
    ];

    protected $casts = [
        'id' => 'integer',
        'sort_order' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Aliases keep the current admin forms working after the column renames.
    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image_path,
            set: fn ($value) => ['image_path' => $value],
        );
    }

    protected function link(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->link_url,
            set: fn ($value) => ['link_url' => $value],
        );
    }

    protected function order(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->sort_order,
            set: fn ($value) => ['sort_order' => $value],
        );
    }

    protected function startDate(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->starts_at,
            set: fn ($value) => ['starts_at' => $value],
        );
    }

    protected function endDate(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->ends_at,
            set: fn ($value) => ['ends_at' => $value],
        );
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => match ($value) {
                true, 1, '1' => 'active',
                false, 0, '0' => 'inactive',
                default => $value,
            },
        );
    }
}
