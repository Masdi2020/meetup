<?php

namespace App\Models;

use App\Enums\BookingAttachmentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

#[Fillable([
    'room_id',
    'user_id',
    'date',
    'start_time',
    'end_time',
    'title',
    'participants',
    'participants_count',
    'notes',
    'status_id',
    'processed_by',
    'processed_at',
    'processed_notes',
])]

/**
 * @property int $id
 * @property string $title
 * @property Carbon $date
 * @property Carbon $start_time
 * @property Carbon $end_time
 * @property int $user_id
 * @property int $participants_count
 * @property Room $room
 * @property BookingStatus $status
 */
class Booking extends Model
{
    use SoftDeletes;

    /**
     * Summary of casts
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'processed_at' => 'datetime',
        'participants_count' => 'integer',
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
        return $this->belongsTo(User::class)->withTrashed();
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

    /** @return HasMany<BookingAttachment, $this> */
    public function documentations(): HasMany
    {
        return $this->attachments()->where('type', BookingAttachmentType::Documentation->value);
    }

    /** @return HasOne<BookingAttachment, $this> */
    public function meetingMinutes(): HasOne
    {
        return $this->hasOne(BookingAttachment::class)
            ->where('type', BookingAttachmentType::MeetingMinutes->value);
    }
}
