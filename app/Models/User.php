<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['name', 'username', 'email', 'password', 'role'])]
#[Hidden(['password'])]
class User extends Authenticatable
{
    /**
     * Summary of casts
     *
     * @return array{password: string}
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
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

    /**
     * Summary of bookingAudits
     *
     * @return HasMany<BookingAudit, $this>
     */
    public function bookingAudits(): HasMany
    {
        return $this->hasMany(BookingAudit::class, 'changed_by');
    }
}
