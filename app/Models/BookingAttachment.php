<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'booking_id',
    'original_filename',
    'filename',
    'path',
    'mime_type',
    'size',
    'uploaded_by',
])]
class BookingAttachment extends Model
{
    public const UPDATED_AT = null;

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
