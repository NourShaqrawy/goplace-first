<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceSlot extends Model
{
   

    protected $fillable = [
        'service_id',
        'slot_date',
        'start_time',
        'end_time',
        'available_capacity',
    ];


    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
