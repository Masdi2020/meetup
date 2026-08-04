<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'booking_id',
    'old_status_id',
    'new_status_id',
    'changed_by',
    'comment',
])]
class BookingAudit extends Model
{
    protected $table = 'booking_audit';

    const UPDATED_AT = null;

    /**
     * Summary of booking
     *
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Summary of oldStatus
     *
     * @return BelongsTo<BookingStatus, $this>
     */
    public function oldStatus(): BelongsTo
    {
        return $this->belongsTo(BookingStatus::class, 'old_status_id');
    }

    /**
     * Summary of newStatus
     *
     * @return BelongsTo<BookingStatus, $this>
     */
    public function newStatus(): BelongsTo
    {
        return $this->belongsTo(BookingStatus::class, 'new_status_id');
    }

    /**
     * Summary of changedBy
     *
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
