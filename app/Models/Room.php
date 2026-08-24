<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'capacity',
    'location',
    'is_available',
    'has_display',
])]

/**
 * @property int $id
 * @property string $name
 */
class Room extends Model
{
    /**
     * Summary of casts
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_available' => 'boolean',
        'has_display' => 'boolean',
    ];

    /**
     * Summary of bookings
     *
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Summary of facilities
     *
     * @return BelongsToMany<Facility, $this>
     */
    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(
            Facility::class,
            'room_facilities',
            'room_id',
            'facility_id'
        );
    }
}
