<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'booking_id',
    'filename',
    'path',
    'mime_type',
    'size',
    'uploaded_by',
])]
class BookingAttachment extends Model
{
    use HasFactory;

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
