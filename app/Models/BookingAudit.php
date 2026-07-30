<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'booking_id',
    'old_status_id',
    'new_status_id',
    'changed_by',
    'comment',
])]
class BookingAudit extends Model
{
    use HasFactory;

    protected $table = 'booking_audit';

    const UPDATED_AT = null;

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function oldStatus()
    {
        return $this->belongsTo(BookingStatus::class, 'old_status_id');
    }

    public function newStatus()
    {
        return $this->belongsTo(BookingStatus::class, 'new_status_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
