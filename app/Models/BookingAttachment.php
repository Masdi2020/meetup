<?php

namespace App\Models;

use App\Enums\BookingAttachmentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'booking_id',
    'original_filename',
    'filename',
    'path',
    'mime_type',
    'size',
    'type',
    'uploaded_by',
])]
class BookingAttachment extends Model
{
    use SoftDeletes;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'type' => BookingAttachmentType::class,
            'size' => 'integer',
        ];
    }

    /**
     * Summary of booking
     *
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by')->withTrashed();
    }
}
