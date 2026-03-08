<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'name',
        'description',
        'fullPrice',
        'book_price',
        'main_image',
        'other_images',
        'city',
        'location',
        'time_to_complete',
        'available_days',
        'available_hours',
        'provider_id',
        'category_id',
        'is_approved',
    ];

    protected $casts = [
        'available_days'   => 'array',
        'available_hours'  => 'array',
        'other_images'     => 'array',
        'is_approved'      => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(ServiceSlot::class);
    }

    // رابط الصورة الرئيسية
    public function getMainImageUrlAttribute()
    {
        return $this->main_image
            ? asset('storage/' . $this->main_image)
            : null;
    }

    // روابط الصور الإضافية
    public function getOtherImagesUrlAttribute()
    {
        if (!$this->other_images || !is_array($this->other_images)) {
            return [];
        }

        return array_map(
            fn ($img) => asset('storage/' . $img),
            $this->other_images
        );
    }
}
