<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'u_username' => 'test_'.Str::lower(Str::random(20)),
            'u_password' => static::$password ??=
                Hash::make('Test-Password-1234'),
            'u_role' => 'admin',
            'tenants_t_id' => null,
            'is_active' => true,
            'must_change_password' => true,
            'remember_token' => Str::random(60),
        ];
    }
}