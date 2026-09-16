<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->u_role === 'admin';
    }

    public function view(User $user, Tenant $tenant): Response
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
            (string) $user->tenants_t_id === (string) $tenant->getKey()
        ) {
            return Response::allow();
        }

        return Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->u_role === 'admin';
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $user->is_active && $user->u_role === 'admin';
    }
}