<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RoomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->u_role === 'admin';
    }

    public function view(User $user, Room $room): Response
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
            $room->rentals()
                ->where('tenants_t_id', $user->tenants_t_id)
                ->where('rt_status', 'ACTIVE')
                ->exists()
        ) {
            return Response::allow();
        }

        return Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->u_role === 'admin';
    }

    public function update(User $user, Room $room): bool
    {
        return $user->is_active && $user->u_role === 'admin';
    }

    public function delete(User $user, Room $room): bool
    {
        return $user->is_active && $user->u_role === 'admin';
    }
}