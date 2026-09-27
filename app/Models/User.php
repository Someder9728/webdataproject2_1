<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'u_username',
    'u_password',
    'u_role',
    'tenants_t_id',
    'is_active',
    'must_change_password',
])]
#[Hidden([
    'u_password',
    'remember_token',
])]
class User extends Authenticatable implements PasskeyUser
{
    /* @use HasFactory<UserFactory> */
    use HasFactory,
        Notifiable,
        PasskeyAuthenticatable,
        TwoFactorAuthenticatable,
        SoftDeletes;

    /* ชื่อตาราง */
    protected $table = 'users';

    /* Primary Key ของ User */
    protected $primaryKey = 'u_id';

    /* Fields ที่สามารถแก้ไขได้ */
    protected $fillable = [
        'u_username',
        'u_password',
        'u_role',
        'tenants_t_id',
        'is_active',
        'must_change_password',
    ];

    /* Cast ข้อมูล */
    protected function casts(): array
    {
        return [
            'u_password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    /* บอก Laravel ว่า Password อยู่ที่ u_password */
    public function getAuthPasswordName(): string
    {
        return 'u_password';
    }

    /* ความสัมพันธ์กับ Tenant */
    public function tenant()
    {
        return $this->belongsTo(
            Tenant::class,
            'tenants_t_id',
            't_id'
        );
    }

    /* Initials สำหรับ Avatar */
    public function initials(): string
    {
        return mb_strtoupper(
            mb_substr((string) $this->u_username, 0, 2)
        );
    }
}