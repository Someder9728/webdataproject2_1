<?php

namespace App\Policies;

use App\Models\Repair;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RepairPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && ! $user->must_change_password
            && in_array($user->u_role, ['admin', 'tenant'], true);
    }

    public function view(User $user, Repair $repair): Response
    {
        if (! $this->viewAny($user)) {
            return Response::deny();
        }

        return Repair::visibleTo($user)->whereKey($repair->getKey())->exists()
            ? Response::allow() : Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Repair $repair): bool
    {
        return $this->viewAny($user) && $user->u_role === 'admin';
    }
}
