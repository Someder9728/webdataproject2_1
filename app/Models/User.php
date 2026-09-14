<?php

namespace App\Models;

use App\Models\Tenant;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable([
    'u_username',
    'u_password',
    'u_role',
    'tenants_t_id',
])]

#[Hidden([
    'u_password',
    'remember_token',
])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory,
        Notifiable,
        PasskeyAuthenticatable,
        TwoFactorAuthenticatable,
        SoftDeletes;

    protected $primaryKey = 'u_id';

    protected $table = 'users';

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenants_t_id', 't_id');
    }

    protected function casts(): array
    {
        return [
            'u_password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    public function getAuthPasswordName(): string
    {
        return 'u_password';
    }

    public function initials(): string
{
    return mb_strtoupper(
        mb_substr((string) $this->u_username, 0, 2)
    );
}
}