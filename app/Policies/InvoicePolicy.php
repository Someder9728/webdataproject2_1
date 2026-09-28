<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active
            && in_array($user->u_role, ['admin', 'tenant'], true);
    }

    public function view(User $user, Invoice $invoice): Response
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
            $invoice->rental()
                ->where('tenants_t_id', $user->tenants_t_id)
                ->exists()
        ) {
            return Response::allow();
        }

        return Response::denyAsNotFound();
    }
}