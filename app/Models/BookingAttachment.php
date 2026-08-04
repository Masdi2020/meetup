<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    public $timestamps = false;

    const CREATED_AT = 'created_at';

    /**
     * Summary of booking
     *
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
