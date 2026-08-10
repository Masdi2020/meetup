<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'booking_id',
    'entity_type',
    'entity_id',
    'old_status_id',
    'new_status_id',
    'changed_by',
    'comment',
    'action',
    'old_values',
    'new_values',
    'ip_address',
])]
class Audit extends Model
{
    protected $table = 'audits';

    public const UPDATED_AT = null;

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Summary of booking
     *
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'entity_id');
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
