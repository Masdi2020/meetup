<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'capacity',
    'floor',
    'calendar_url',
])]
class Room extends Model
{
    public function bookings() {
        return $this->hasMany(Booking::class);
    }

    public function facilities() {
        return $this->belongsToMany(
            Facility::class,
            'room_facilities',
            'room_id',
            'facility_id'
        );
    }
}
