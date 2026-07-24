<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable([
    'name',
])]
class Facility extends Model
{
    use HasFactory;

    public function rooms() {
        return $this->belongsToMany(
            Room::class,
            'room_facilities'
        );
    }
}
