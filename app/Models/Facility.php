<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
])]
class Facility extends Model
{
    use SoftDeletes;

    /**
     * Summary of rooms
     *
     * @return BelongsToMany<Room, $this>
     */
    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(
            Room::class,
            'room_facilities'
        );
    }
}
