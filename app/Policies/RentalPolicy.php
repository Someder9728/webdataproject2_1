<?php

namespace App\Policies;

use App\Models\Rental;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RentalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active
            && in_array($user->u_role, ['admin', 'tenant'], true);
    }

    public function view(User $user, Rental $rental): Response
    {
        if (! $user->is_active) {
            return Response::deny();
        }

        if ($user->u_role === 'admin') {
            return Response::allow();
        }

        if (
            $user->u_role === 'tenant' &&
            $user->tenants_t_id !== null &&
            (string) $user->tenants_t_id === (string) $rental->tenants_t_id
        ) {
            return Response::allow();
        }

        return Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->u_role === 'admin';
    }

    public function moveOut(User $user, Rental $rental): bool
    {
        return $user->is_active && $user->u_role === 'admin';
    }
}