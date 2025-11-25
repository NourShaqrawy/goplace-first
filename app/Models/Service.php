<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Service extends Model
{
protected $fillable = [
        'name', 'description', 'price', 'capacity',
        'provider_id', 'category_id', 'is_approved','image',
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
public function getImageUrlAttribute()
{
    return $this->image ? asset('storage/' . $this->image) : null;
}

}
