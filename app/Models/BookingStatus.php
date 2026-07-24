<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable([
    'code',
    'label',
])]
class BookingStatus extends Model
{
    use HasFactory;

    public function bookings() {
        return $this->hasMany(Booking::class);
    }

    public function oldStatusAudits() {
        return $this->hasMany(BookingAudit::class, 'old_status_id');
    }

    public function newStatusAudits() {
        return $this->hasMany(BookingAudit::class, 'new_status_id');
    }
}
