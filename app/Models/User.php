<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['name', 'username', 'password', 'role', 'force_change_password'])]
#[Hidden(['password'])]
class User extends Authenticatable
{
    /**
     * Summary of casts
     *
     * @return array{password: 'hashed', force_change_password: 'bool'}
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'force_change_password' => 'bool',
        ];
    }

    /**
     * Summary of bookings
     *
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
