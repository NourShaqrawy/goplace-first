<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{

  protected $fillable = [
    'name',
    'image'
];

public function getImageUrlAttribute()
{
    return $this->image ? asset('storage/' . $this->image) : null;
}

protected $appends = ['image_url'];
}
