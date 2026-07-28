<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
class Booking extends Model
{
    use HasFactory;

    protected function casts(): array {
        return [
            'date' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
        ];
    }

    public function room() {
        return $this->belongsTo(Room::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function status() {
        return $this->belongsTo(BookingStatus::class);
    }

    public function attachments() {
        return $this->hasMany(BookingAttachment::class);
    }

    public function audits() {
        return $this->hasMany(BookingAudit::class);
    }
}
