<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['name', 'username', 'password', 'role_id', 'force_change_password'])]
#[Hidden(['password', 'active_username', 'assignedRole'])]
class User extends Authenticatable
{
    use SoftDeletes;

    /** @var list<string> */
    protected $with = ['assignedRole'];

    /** @var list<string> */
    protected $appends = ['role'];

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->role_id ??= Role::where('code', 'user')->firstOrFail()->id;
        });
    }

    /** @return BelongsTo<Role, $this> */
    public function assignedRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /** @return Attribute<string|null, never> */
    protected function role(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->assignedRole?->code);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin'], true);
    }

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
