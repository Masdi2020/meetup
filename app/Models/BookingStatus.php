<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'label',
])]

/**
 * @property int $id
 * @property string $label
 */
class BookingStatus extends Model
{
    /**
     * Summary of bookings
     *
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'status_id');
    }

    /**
     * Summary of oldStatusAudits
     *
     * @return HasMany<BookingAudit, $this>
     */
    public function oldStatusAudits(): HasMany
    {
        return $this->hasMany(BookingAudit::class, 'old_status_id');
    }

    /**
     * Summary of newStatusAudits
     *
     * @return HasMany<BookingAudit, $this>
     */
    public function newStatusAudits(): HasMany
    {
        return $this->hasMany(BookingAudit::class, 'new_status_id');
    }
}
