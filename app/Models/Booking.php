<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'room_id',
    'user_id',
    'date',
    'start_time',
    'end_time',
    'title',
    'participants',
    'status_id',
])]

/**
 * @property int $id
 * @property string $title
 * @property Carbon $date
 * @property Carbon $start_time
 * @property Carbon $end_time
 * @property int $user_id
 * @property Room $room
 * @property Status $status
 */
class Booking extends Model
{
    /**
     * Summary of casts
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    /**
     * Summary of room
     *
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Summary of user
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Summary of status
     *
     * @return BelongsTo<BookingStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(BookingStatus::class, 'status_id');
    }

    /**
     * Summary of attachments
     *
     * @return HasMany<BookingAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(BookingAttachment::class);
    }

    /**
     * Summary of audits
     *
     * @return HasMany<BookingAudit, $this>
     */
    public function audits(): HasMany
    {
        return $this->hasMany(BookingAudit::class);
    }
}
